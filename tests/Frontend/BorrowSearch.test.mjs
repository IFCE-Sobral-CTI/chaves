import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import vm from 'node:vm';

const moduleSource = (await readFile(new URL('../../resources/js/Components/Dashboard/useIndexSearch.js', import.meta.url), 'utf8'))
    .replace(/^import .*;\s*/gm, '')
    .replace('export default function useIndexSearch', 'function useIndexSearch');

function createSearch(route, initialTerm = '') {
    const state = [];
    const effects = [];
    const timers = new Map();
    const calls = [];
    let nextTimerId = 0;
    let hookIndex = 0;
    let pendingEffects = [];
    let cancelled = false;

    const useIndexSearch = vm.runInNewContext(`(() => {${moduleSource}; return useIndexSearch;})()`, {
        useState(initialValue) {
            const index = hookIndex++;
            state[index] ??= initialValue;
            return [state[index], value => { state[index] = value; }];
        },
        useEffect(callback, dependencies) {
            const index = hookIndex++;
            pendingEffects[index] = { callback, dependencies };
        },
        setTimeout(callback, delay) {
            assert.equal(delay, 300);
            const id = ++nextTimerId;
            timers.set(id, callback);
            return id;
        },
        clearTimeout(id) {
            timers.delete(id);
        },
        router: {
            get(url, data, options) {
                calls.push({ url, data: { ...data }, preserveState: options.preserveState, replace: options.replace });
                options.onCancelToken({ cancel() { cancelled = true; } });
            },
        },
    });

    function render(termSearch = initialTerm) {
        hookIndex = 0;
        pendingEffects = [];
        const result = useIndexSearch(route, termSearch);
        for (const [index, next] of pendingEffects.entries()) {
            if (!next) continue;
            const previous = effects[index];
            const changed = !previous || next.dependencies.some((value, dependencyIndex) => value !== previous.dependencies[dependencyIndex]);
            if (changed) {
                previous?.cleanup?.();
                effects[index] = { dependencies: next.dependencies, cleanup: next.callback() };
            }
        }
        return result;
    }

    return {
        calls,
        render,
        flush() {
            const [id, callback] = timers.entries().next().value ?? [];
            if (id !== undefined) {
                timers.delete(id);
                callback();
            }
        },
        get pendingTimers() { return timers.size; },
        get cancelled() { return cancelled; },
    };
}

test('Borrow and Block lists do not request their current term on mount', () => {
    for (const route of ['borrows.index', 'blocks.index']) {
        for (const term of ['', 'Maria']) {
            const list = createSearch(route, term);
            list.render();
            assert.deepEqual(list.calls, []);
            assert.equal(list.pendingTimers, 0);
        }
    }
});

test('Borrow and Block searches and clears always request page one', () => {
    for (const route of ['borrows.index', 'blocks.index']) {
        for (const [term, previous] of [['Maria', ''], ['', 'Maria']]) {
            const list = createSearch(route, previous);
            const input = list.render();
            input.setTerm(term);
            list.render(previous);
            list.flush();
            assert.deepEqual(list.calls, [{
                url: route, data: { term, page: 1 }, preserveState: true, replace: true,
            }]);
        }
    }
});

test('changing the term or leaving the page cancels a pending or active visit', () => {
    const waiting = createSearch('borrows.index');
    waiting.render().setTerm('Maria');
    waiting.render();
    assert.equal(waiting.pendingTimers, 1);
    waiting.render().setTerm('João');
    waiting.render();
    assert.equal(waiting.pendingTimers, 1);
    waiting.flush();
    assert.deepEqual(waiting.calls[0].data, { term: 'João', page: 1 });

    const active = createSearch('blocks.index');
    active.render().setTerm('Bloco A');
    active.render();
    active.flush();
    active.render().setTerm('Bloco B');
    active.render();
    assert.equal(active.cancelled, true);
});

test('server acknowledgement and browser navigation keep the input in sync', () => {
    const list = createSearch('borrows.index');
    list.render('Maria').setTerm('Ana');
    list.render('Maria');
    list.flush();
    assert.equal(list.calls.length, 1);

    const acknowledged = list.render('Ana');
    assert.equal(acknowledged.term, 'Ana');
    assert.equal(list.pendingTimers, 0);

    list.render('Maria');
    const navigatedBack = list.render('Maria');
    assert.equal(navigatedBack.term, 'Maria');
    assert.equal(list.pendingTimers, 0);
});
