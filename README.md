# ESQUELETO PARA APLICAÇÕES DO IFCE - CAMPUS SOBRAL

## FERRAMENTAS USADAS

* Laravel
* Inertia
* ReactJS

## INSTALAÇÃO
Para instalar faça um clone do projeto:

```git clone https://github.com/CTI-Sobral-IFCE/skeleton.git meu-projeto```

Acesse o diretório do projeto:

```cd meu-projeto```

Remova o repositório do Esqueleto de Aplicação:

```git remote remove origin```

Adicione o repositório do seu projeto.

```git remote add origin https://github.com/USER/REPO>.git```

Instale os pacotes do PHP/Laravel:

```composer update```

Instale os pacotes Javascript/Inertia/Javascript:

```npm install```

Faça uma cópia do arquivo ```.env```:

```cp .env.example .env```

Abra o arquivo ```.env``` e preencha com as configurações(Nome, url, banco de dados) do seu projeto.

Gere a chave de segurança da sua aplicação Laravel:

```php artisan key:generate```

Para executar o projeto depois das configurações:

```npm run dev```

