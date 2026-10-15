import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import vm from 'node:vm';

// Execute the component's actual effect with controlled hooks and timers.
const source = await readFile(new URL('../../resources/js/Pages/Keys/Borrow/Index.jsx', import.meta.url), 'utf8');
const effect = source.slice(source.indexOf('    useEffect(() => {'), source.indexOf('    const status ='));

function search(term, termSearch = '', page = 7) {
    let run;
    let cleanup;
    let cancelled = false;
    const calls = [];
    vm.runInNewContext(effect, {
        term, termSearch, page,
        useEffect(callback) { cleanup = callback(); },
        setTimeout(callback, delay) { assert.equal(delay, 300); run = callback; return 1; },
        clearTimeout() { run = undefined; },
        route: name => name,
        router: { get(url, data, options) {
            calls.push({ url, data: { ...data }, preserveState: options.preserveState, replace: options.replace });
            options.onCancelToken({ cancel() { cancelled = true; } });
        } },
    });
    return {
        calls,
        flush() { run?.(); },
        cleanup() { cleanup?.(); },
        get cancelled() { return cancelled; },
    };
}

test('opening the list with or without a search does not request it again', () => {
    for (const term of ['', 'Maria']) {
        const visit = search(term, term || null);
        visit.flush();
        assert.deepEqual(visit.calls, []);
    }
});

test('a changed or cleared search starts on page one and preserves component state', () => {
    for (const [term, previous] of [['Maria', ''], ['', 'Maria']]) {
        const visit = search(term, previous);
        assert.equal(visit.calls.length, 0);
        visit.flush();
        assert.deepEqual(visit.calls, [{
            url: 'borrows.index', data: { term, page: 1 }, preserveState: true, replace: true,
        }]);
    }
});

test('changing the term or leaving the page clears the debounce and cancels an active visit', () => {
    const waiting = search('Maria');
    waiting.cleanup();
    waiting.flush();
    assert.deepEqual(waiting.calls, []);

    const active = search('Maria');
    active.flush();
    active.cleanup();
    assert.equal(active.cancelled, true);
});

test('the server acknowledging the new term does not schedule another search', () => {
    const visit = search('Maria', 'Maria', 1);
    visit.flush();
    assert.deepEqual(visit.calls, []);
});
