FROM ubuntu:jammy

ENV TERM linux
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && apt-get install -y \
  gnumeric \
  pgloader \
  php-cli \
  postgresql-client \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /app

CMD ["/bin/bash"]
