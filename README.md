# Sistema de Empréstimos de Chaves

## Sistema desenvolvido em

* [Laravel](https://laravel.com/)
* [Inertia](https://inertiajs.com/)
* [ReactJS](https://reactjs.org/)
* [Tailwind CSS](https://tailwindcss.com/)
* [Tailwind Elements](https://tailwind-elements.com/)

> As versões dos pacotes usados pelo sistema estão nos arquivos [composer.json](composer.json)  e [package.json](package.json)

## Instalação

A instalação(local) desse sistema é bem simples, bastando apenas seguir os passos a baixo:

> Lembando que para rodas os comando abaixo o [Docker](https://www.docker.com/) deve está instado em sua computador.
> Os passos abaixo usa o [Sail](https://laravel.com/docs/9.x/sail), pacote do [Laravel](https://laravel.com/).

1. Faça o clone desse projeto em seu computador

    ```sh
    git clone https://github.com/CTI-Sobral-IFCE/chaves.git
    ```

2. Acesse o diretório do projeto

    ```sh
    cd chaves
    ```

3. Instale os pacotes usados pelo projeto (PHP/composer)

    ```sh
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v $(pwd):/var/www/html \
        -w /var/www/html \
        laravelsail/php81-composer:latest \
        composer install --ignore-platform-reqs
    ```

4. Faça a cópia do arquivos ```.env.example``` para ```.env``` e preencha com os dados de acesso ao banco de dados e demais configurações.

    ```sh
    cp .env.example .env
    ```

5. Execute o projeto.

    ```sh
    ./vendor/bin/sail up -d
    ```

6. Instale as bibliotecas javascript

    ```sh
    ./vendor/bin/sail npm install && ./vendor/bin/sail npm build
    ```

7. Povoe o banco de dados.

    ```sh
    ./vendor/bin/sail artisan migrate:fresh --seed
    ```

8. Para acessar o sistema.

* Endereço: [http://localhost/](http://localhost/)
* Dados de acesso:
  * Login: ti.sobral@ifce.edu.br
  * Senha: qwe123

## Documentação

Para mais informações sobre o sistema acesse a [wiki](https://github.com/CTI-Sobral-IFCE/chaves/wiki).
  
## Licença

Este sistema é de código aberto e está licenciado sob a [licença MIT](https://opensource.org/licenses/MIT).
