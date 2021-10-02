FROM php:8.0-fpm-alpine
FROM node:16-alpine3.11

RUN apk add --no-cache su-exec && \
    addgroup bar && \
    adduser -D -h /home -s /bin/sh -G bar foo

ADD entrypoint.sh /entrypoint

ENTRYPOINT ["/entrypoint"]