##
# https://docs.docker.com/build/building/best-practices/
#

# https://hub.docker.com/r/manticoresearch/manticore
FROM manticoresearch/manticore

# install php for config parsing
RUN apt-get update \
    && apt-get install -y php-cli \
    && rm -rf /var/lib/apt/lists/*

# parse the manticore config
COPY entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
