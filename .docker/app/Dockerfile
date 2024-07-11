FROM ghcr.io/glpi-project/glpi-development-env:latest

USER root

# Workaround to make apache use same UID/GID as host user.
ARG HOST_GROUP_ID=1000
RUN groupmod --gid ${HOST_GROUP_ID} www-data
ARG HOST_USER_ID=1000
RUN usermod --uid ${HOST_USER_ID} www-data

# Allow login as www-data user and allow it to execute sudo
RUN usermod --shell /bin/bash www-data
RUN echo "www-data ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

# Make www-data user home persistent for cache purpose.
RUN mkdir --parents /home/www-data \
  && chown www-data:www-data /home/www-data \
  && usermod --home /home/www-data www-data
VOLUME /home/www-data

USER www-data
