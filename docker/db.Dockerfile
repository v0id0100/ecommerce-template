FROM mysql:8.0
ENV MYSQL_ROOT_PASSWORD=rootpassword
ENV MYSQL_DATABASE=ecommerce_db
COPY backend/src/init.sql /docker-entrypoint-initdb.d/