[English](README.md) | [Русский](README.ru.md)

# Bitrix Docker Template

A reusable local Docker development template for 1C-Bitrix projects. Docker infrastructure stays in this repository, while the Bitrix project lives separately in `www`, so multiple template copies can run independently. It provides a local HTTPS entry point on standard port 443, plus PHP, MySQL, Redis, Mailpit, Push & Pull, phpMyAdmin, Composer, and Node.js tooling.

## Requirements

- Git
- Docker with Docker Compose (`docker compose`)
- `mkcert` for HTTPS certificates; run `mkcert -install` once to trust its local CA. On Linux, install `certutil` as required by `mkcert` (for example, the `libnss3-tools` package on Debian/Ubuntu).
- `curl` for the Bitrix installation and restoration preparation commands

## Quick start: fresh Bitrix installation

```bash
git clone https://github.com/Oleg4cy/bitrix-docker-template.git
cd bitrix-docker-template
./bin/dev up
./bin/dev bitrix install
```

`./bin/dev up` starts the local Docker environment. `./bin/dev bitrix install` downloads and prepares the official Bitrix installer. Open the HTTPS URL printed by the helper and continue the installation in the browser.

## Quick start: backup restoration

For restoration from a Bitrix backup:

```bash
git clone https://github.com/Oleg4cy/bitrix-docker-template.git
cd bitrix-docker-template
./bin/dev up
./bin/dev bitrix restore
```

`./bin/dev up` starts the environment. `./bin/dev bitrix restore` downloads and prepares the official Bitrix restore script. Open the HTTPS URL printed by the helper and continue with the Bitrix restoration wizard.

## Bitrix database settings

These settings apply to both a fresh Bitrix installation and a restored Bitrix project whenever database connection details are requested:

| Field | Value |
| --- | --- |
| Database server | `mysql` |
| Database user | `bitrix` |
| Database password | `bitrix` |
| Database | Existing |
| Database name | `bitrix` |
| File permissions | `0644` |
| Directory permissions | `0755` |

Do not use `localhost` or `127.0.0.1` as the database server. PHP and MySQL run in different Compose services: the PHP installer runs in `php`, while Docker's internal DNS resolves `mysql` to the MySQL container. MySQL uses internal port `3306`; do not enter an external or phpMyAdmin port.

The database and database user are already created by the template, so normally select the existing database and user rather than asking Bitrix to create a new database. If `MYSQL_DATABASE`, `MYSQL_USER`, or `MYSQL_PASSWORD` is changed in `.env`, use the same values in Bitrix.

## Repository separation

```text
bitrix-docker-template/
├── compose.yaml
├── bin/
├── docker/
└── www/        # separate Bitrix project repository
```

The template repository ignores `www`. The Bitrix project inside `www` may use its own `.git` directory and `.gitignore` without mixing application history with infrastructure history.

## Local URLs

Each template copy automatically receives its own loopback IP in the `127.0.0.0/8` range. The site is exposed through standard HTTPS port 443, so multiple template copies can run independently without giving Bitrix a non-standard external HTTPS port. Nginx is the single host-facing entry point:

- Bitrix: `https://<APP_HOST_IP>/`
- phpMyAdmin: `https://<APP_HOST_IP>/phpmyadmin/`
- Mailpit: `https://<APP_HOST_IP>/mailpit/`

phpMyAdmin and Mailpit have no separate host ports; nginx proxies them internally through Docker service names. No manual host networking or system configuration is required by the template. Display the actual generated URLs with:

```bash
./bin/dev urls
```

## Bitrix project Git repository

The template repository intentionally ignores the entire `www` directory, so files of the actual Bitrix project never enter the Git history of `bitrix-docker-template`. If version control is needed for the Bitrix project itself, initialize a separate repository inside `www`:

```bash
git -C www init -b main
```

This produces two independent repositories:

```text
bitrix-docker-template/.git
bitrix-docker-template/www/.git
```

Both Git initialization and `.gitignore` creation are optional and independent. The helper can optionally create the recommended `.gitignore` for the Bitrix project:

```bash
./bin/dev gitignore
```

It creates `www/.gitignore` only if it does not already exist and never overwrites an existing file. It is not automatically executed by `bitrix install` or `bitrix restore`, which is especially important during restoration because the restored project may already contain its own `.gitignore`.

## Commands

| Command | Arguments | Description |
| --- | --- | --- |
| `./bin/dev up [docker-compose-up-args]` | Optional arguments forwarded to [`docker compose up`](https://docs.docker.com/reference/cli/docker/compose/up/). | Start services. |
| `./bin/dev down [docker-compose-down-args]` | Optional arguments forwarded to [`docker compose down`](https://docs.docker.com/reference/cli/docker/compose/down/). | Stop and remove project containers. |
| `./bin/dev restart [docker-compose-restart-args] [service...]` | Arguments forwarded to [`docker compose restart`](https://docs.docker.com/reference/cli/docker/compose/restart/); optionally select services. | Restart services. |
| `./bin/dev status [docker-compose-ps-args]` | Optional arguments forwarded to [`docker compose ps`](https://docs.docker.com/reference/cli/docker/compose/ps/). | Show service status. |
| `./bin/dev ps [docker-compose-ps-args]` | Optional arguments forwarded to [`docker compose ps`](https://docs.docker.com/reference/cli/docker/compose/ps/); alias for `status`. | Show service status. |
| `./bin/dev urls` | None. | Show generated local URLs. |
| `./bin/dev gitignore` | None. | Create `www/.gitignore` if it does not exist. |
| `./bin/dev shell [bash-args]` | Optional arguments passed to `bash` through [`docker compose exec`](https://docs.docker.com/reference/cli/docker/compose/exec/) in the PHP container. | Open Bash in the PHP container. |
| `./bin/dev root [bash-args]` | Optional arguments passed to root's `bash` through [`docker compose exec`](https://docs.docker.com/reference/cli/docker/compose/exec/) in the PHP container. | Open a root Bash shell in the PHP container. |
| `./bin/dev composer [composer-args]` | Arguments passed to Composer through [`docker compose exec`](https://docs.docker.com/reference/cli/docker/compose/exec/) in the PHP container. | Run Composer. |
| `./bin/dev npm [npm-args]` | Arguments passed to npm through [`docker compose run`](https://docs.docker.com/reference/cli/docker/compose/run/) in the temporary Node.js toolbox container. | Run npm. |
| `./bin/dev logs [docker-compose-logs-args] [service...]` | Arguments forwarded to [`docker compose logs`](https://docs.docker.com/reference/cli/docker/compose/logs/) with `-f`. | Follow logs. |
| `./bin/dev mysql` | Opens the database through [`docker compose exec`](https://docs.docker.com/reference/cli/docker/compose/exec/). | Open the project database in the MySQL CLI. |
| `./bin/dev redis [redis-cli-args]` | Arguments passed to `redis-cli` through [`docker compose exec`](https://docs.docker.com/reference/cli/docker/compose/exec/). | Run `redis-cli`. |
| `./bin/dev reset` | After confirmation, runs [`docker compose down`](https://docs.docker.com/reference/cli/docker/compose/down/) with `--volumes`. | Remove project containers and volumes, including database data. |
| `./bin/dev bitrix {install|restore|configure|cleanup}` | One required subcommand: `install`, `restore`, `configure`, or `cleanup`. | Prepare, configure, or clean up a Bitrix installation or restoration. |
| `./bin/dev help` | None. | Show command help. |

### Command examples

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

Arguments forwarded to Docker Compose, Bash, Composer, npm, or `redis-cli` follow the corresponding underlying command's syntax.
