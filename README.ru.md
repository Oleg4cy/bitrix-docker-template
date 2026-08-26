[English](README.md) | [Русский](README.ru.md)

# Шаблон Docker для Bitrix

Многократно используемый шаблон локальной Docker-среды для проектов на «1С-Битрикс». Docker-инфраструктура хранится в этом репозитории, а сам проект на Bitrix — отдельно в `www`, поэтому несколько копий шаблона могут работать независимо. Для локальной разработки используется единая HTTPS-точка входа на стандартном порту 443, а также доступны PHP, MySQL, Redis, Mailpit, Push & Pull, phpMyAdmin, Composer и инструменты Node.js.

## Требования

- Git
- Docker с Docker Compose (`docker compose`)
- `mkcert` для HTTPS-сертификатов; один раз выполните `mkcert -install`, чтобы добавить локальный центр сертификации в доверенные. В Linux установите `certutil`, необходимый для `mkcert` (например, пакет `libnss3-tools` в Debian/Ubuntu).
- `curl` для команд подготовки установки и восстановления Bitrix

## Быстрый старт: чистая установка Bitrix

```bash
git clone https://github.com/Oleg4cy/bitrix-docker-template.git
cd bitrix-docker-template
./bin/dev up
./bin/dev bitrix install
```

`./bin/dev up` запускает локальное Docker-окружение. `./bin/dev bitrix install` скачивает и подготавливает официальный установщик Bitrix. Откройте HTTPS-адрес, напечатанный вспомогательной командой, и продолжите установку в браузере.

## Быстрый старт: восстановление из резервной копии

Для восстановления из резервной копии Bitrix выполните:

```bash
git clone https://github.com/Oleg4cy/bitrix-docker-template.git
cd bitrix-docker-template
./bin/dev up
./bin/dev bitrix restore
```

`./bin/dev up` запускает окружение. `./bin/dev bitrix restore` скачивает и подготавливает официальный скрипт восстановления Bitrix. Откройте HTTPS-адрес, напечатанный вспомогательной командой, и продолжите работу с мастером восстановления Bitrix.

## Параметры базы данных Bitrix

Эти параметры применяются и для чистой установки Bitrix, и для восстановления проекта Bitrix, когда запрашиваются данные подключения к базе данных:

| Поле | Значение |
| --- | --- |
| Сервер базы данных | `mysql` |
| Пользователь базы данных | `bitrix` |
| Пароль базы данных | `bitrix` |
| База данных | Существующая |
| Имя базы данных | `bitrix` |
| Права на файлы | `0644` |
| Права на директории | `0755` |

Не используйте `localhost` или `127.0.0.1` в качестве сервера БД. PHP и MySQL работают в разных сервисах Compose: установщик работает в `php`, а внутренний DNS Docker разрешает имя `mysql` в адрес контейнера MySQL. MySQL использует внутренний порт `3306`; внешний порт или порт phpMyAdmin указывать не нужно.

База данных и пользователь БД уже создаются шаблоном, поэтому обычно следует выбрать существующие базу и пользователя, а не поручать Bitrix создание новой базы. Если в `.env` изменены `MYSQL_DATABASE`, `MYSQL_USER` или `MYSQL_PASSWORD`, используйте в Bitrix те же значения.

## Разделение репозиториев

```text
bitrix-docker-template/
├── compose.yaml
├── bin/
├── docker/
└── www/        # отдельный репозиторий проекта на Bitrix
```

Репозиторий шаблона игнорирует `www`. Проект на Bitrix внутри `www` может использовать собственные `.git` и `.gitignore`, чтобы история приложения не смешивалась с историей инфраструктуры.

## Локальные адреса

Каждая копия шаблона автоматически получает собственный loopback-IP из диапазона `127.0.0.0/8`. Сайт доступен через стандартный HTTPS-порт 443, поэтому несколько копий шаблона могут работать независимо без нестандартного внешнего HTTPS-порта для Bitrix. Nginx является единственной точкой входа с хоста:

- Bitrix: `https://<APP_HOST_IP>/`
- phpMyAdmin: `https://<APP_HOST_IP>/phpmyadmin/`
- Mailpit: `https://<APP_HOST_IP>/mailpit/`

Для phpMyAdmin и Mailpit не публикуются отдельные порты хоста: nginx проксирует их внутри Docker по именам сервисов. Шаблону не требуется ручная настройка сети или системы. Выведите фактически сгенерированные адреса командой:

```bash
./bin/dev urls
```

## Git-репозиторий проекта Bitrix

Репозиторий шаблона намеренно игнорирует весь каталог `www`, поэтому файлы самого проекта Bitrix никогда не попадают в историю Git репозитория `bitrix-docker-template`. Если для проекта Bitrix нужен контроль версий, инициализируйте отдельный репозиторий внутри `www`:

```bash
git -C www init -b main
```

В результате существуют два независимых репозитория:

```text
bitrix-docker-template/.git
bitrix-docker-template/www/.git
```

Инициализация Git и создание `.gitignore` — необязательные и независимые действия. Вспомогательная команда может по желанию создать рекомендуемый `.gitignore` для проекта Bitrix:

```bash
./bin/dev gitignore
```

Команда создаёт `www/.gitignore`, только если он ещё не существует, и никогда не перезаписывает существующий файл. Она не запускается автоматически командами `bitrix install` или `bitrix restore`; это особенно важно при восстановлении, поскольку восстановленный проект может уже содержать собственный `.gitignore`.

## Команды

| Команда | Аргументы | Описание |
| --- | --- | --- |
| `./bin/dev up [docker-compose-up-args]` | Необязательные аргументы передаются в [`docker compose up`](https://docs.docker.com/reference/cli/docker/compose/up/). | Запускает сервисы. |
| `./bin/dev down [docker-compose-down-args]` | Необязательные аргументы передаются в [`docker compose down`](https://docs.docker.com/reference/cli/docker/compose/down/). | Останавливает и удаляет контейнеры проекта. |
| `./bin/dev restart [docker-compose-restart-args] [service...]` | Аргументы передаются в [`docker compose restart`](https://docs.docker.com/reference/cli/docker/compose/restart/); можно указать сервисы. | Перезапускает сервисы. |
| `./bin/dev status [docker-compose-ps-args]` | Необязательные аргументы передаются в [`docker compose ps`](https://docs.docker.com/reference/cli/docker/compose/ps/). | Показывает состояние сервисов. |
| `./bin/dev ps [docker-compose-ps-args]` | Необязательные аргументы передаются в [`docker compose ps`](https://docs.docker.com/reference/cli/docker/compose/ps/); псевдоним `status`. | Показывает состояние сервисов. |
| `./bin/dev urls` | Нет. | Показывает сгенерированные локальные адреса. |
| `./bin/dev gitignore` | Нет. | Создаёт `www/.gitignore`, если файл ещё не существует. |
| `./bin/dev shell [bash-args]` | Необязательные аргументы передаются в `bash` PHP-контейнера через [`docker compose exec`](https://docs.docker.com/reference/cli/docker/compose/exec/). | Открывает Bash в PHP-контейнере. |
| `./bin/dev root [bash-args]` | Необязательные аргументы передаются в `bash` от root через [`docker compose exec`](https://docs.docker.com/reference/cli/docker/compose/exec/). | Открывает Bash от root в PHP-контейнере. |
| `./bin/dev composer [composer-args]` | Аргументы передаются Composer в PHP-контейнере через [`docker compose exec`](https://docs.docker.com/reference/cli/docker/compose/exec/). | Запускает Composer. |
| `./bin/dev npm [npm-args]` | Аргументы передаются npm во временном контейнере через [`docker compose run`](https://docs.docker.com/reference/cli/docker/compose/run/). | Запускает npm. |
| `./bin/dev logs [docker-compose-logs-args] [service...]` | Аргументы передаются в [`docker compose logs`](https://docs.docker.com/reference/cli/docker/compose/logs/) с `-f`. | Показывает логи в реальном времени. |
| `./bin/dev mysql` | Открывает базу через [`docker compose exec`](https://docs.docker.com/reference/cli/docker/compose/exec/). | Открывает базу проекта в консольном клиенте MySQL. |
| `./bin/dev redis [redis-cli-args]` | Аргументы передаются в `redis-cli` через [`docker compose exec`](https://docs.docker.com/reference/cli/docker/compose/exec/). | Запускает `redis-cli`. |
| `./bin/dev reset` | После подтверждения выполняет [`docker compose down`](https://docs.docker.com/reference/cli/docker/compose/down/) с `--volumes`. | Удаляет контейнеры и тома проекта, включая данные базы. |
| `./bin/dev bitrix {install|restore|configure|cleanup}` | Один обязательный подкомандный аргумент: `install`, `restore`, `configure` или `cleanup`. | Подготавливает, настраивает или очищает установку/восстановление Bitrix. |
| `./bin/dev help` | Нет. | Показывает справку по командам. |

### Примеры команд

```bash
./bin/dev up --build
./bin/dev up --force-recreate
./bin/dev down --remove-orphans
./bin/dev gitignore
./bin/dev restart nginx
./bin/dev status --services
./bin/dev ps --services
./bin/dev shell -c 'php -v'
./bin/dev composer install
./bin/dev composer require vendor/package
./bin/dev npm install
./bin/dev npm run build
./bin/dev logs php
./bin/dev logs --tail=100 nginx
./bin/dev redis ping
./bin/dev bitrix install
./bin/dev bitrix restore
./bin/dev bitrix configure
./bin/dev bitrix cleanup
```

Аргументы, передаваемые Docker Compose, Bash, Composer, npm или `redis-cli`, соответствуют синтаксису соответствующей исходной команды.
