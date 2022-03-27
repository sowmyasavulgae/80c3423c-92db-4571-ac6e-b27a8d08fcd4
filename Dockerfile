FROM php:cli-alpine
COPY . /usr/src/reporter
WORKDIR /usr/src/reporter
CMD [ "./index.php" ]
