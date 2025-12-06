# Fixturization
Create database for dev from production or rc.

## Installation:
```shell
composer require glider88/fixturization
```

## Run example:

Get example data:
```shell
wget https://raw.githubusercontent.com/devrimgunduz/pagila/refs/heads/master/pagila-schema.sql -P .docker/postgres
```
```shell
wget https://raw.githubusercontent.com/devrimgunduz/pagila/refs/heads/master/pagila-data.sql -P .docker/postgres
```

Fix owner:
```shell
sed -i -e 's/OWNER TO postgres/OWNER TO fixturization/g' .docker/postgres/pagila-schema.sql
```

Start docker:

First time:
```shell
bin/reup
```
Next times:
```shell
bin/up
```

Generate schema from database:
```shell
bin/sh php examples/schema.php psql
```

Generate dump (sql and yaml formats):
```shell
bin/sh php examples/crawler.php sql yaml
```
