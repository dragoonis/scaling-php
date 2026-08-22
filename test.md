### opcache introduction

How the OPcache stats leave each runtime and reach the dashboard (interactive version:
[docs/diagrams/opcache-metrics-pipeline.html](docs/diagrams/opcache-metrics-pipeline.html)):

![OPcache exporter pipeline](docs/images/opcache-metrics-pipeline.png)

```
make up-opcache-dashboard
```

open - http://localhost:42042/opcache/status

![img.png](docs/images/opcache-dashboard.png)

https://www.php.net/manual/en/opcache.configuration.php#:~:text=on%20all%20architectures.-,opcache.max_accelerated_files,-int

```
find . -type f -name "*.php" | wc -l
```

```
opcache.max_accelerated_files=16087
```

Prime Number: 10000 -> 10007 (nearest prime)
