FROM mysql:lts

COPY docker/mysql/conf.d/lab.cnf /etc/mysql/conf.d/lab.cnf
COPY docker/mysql/init/ /docker-entrypoint-initdb.d/

RUN chmod 0644 /etc/mysql/conf.d/lab.cnf \
    && chmod 0755 /docker-entrypoint-initdb.d/*.sh \
    && chmod 0644 /docker-entrypoint-initdb.d/*.sql
