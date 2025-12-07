# Fixturization

Select the start of the table crawl, after which the spider will follow the table refs and collect data.
Determine which columns are needed, which filters to use, how many rows select from the table, and how to transform the data after fetching.
This data can be used for fixtures or tests.

### Installation:
```shell
composer require glider88/fixturization
```

### Learn by example:

See how you can use library in `examples/`

Get data:
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
```shell
bin/reup  # first time
```
```shell
bin/up  # next times
```

Generate schema from database:
```shell
bin/sh php examples/schema.php psql
```
Additional description of the schema that is not in the database: `examples/var/schema/manual.yaml`

Generate dump (support sql and yaml formats):
```shell
bin/sh php examples/crawler.php sql yaml
```
