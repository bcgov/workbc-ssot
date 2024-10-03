FROM ubuntu:jammy

ENV TERM linux
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && apt-get install -y \
  software-properties-common \
  gnumeric \
  pgloader \
  php-cli \
  postgresql-client \
  && add-apt-repository -y ppa:mithrandie/csvq \
  && apt-get install -y csvq \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /app

CMD ["/bin/bash"]
