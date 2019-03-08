FROM mysql:5.7

LABEL \
    "com.trilogy.product"="GLPI" \
    "com.trilogy.company"="External" \
    "com.trilogy.stage"="dev" \
    "com.trilogy.maintainer.skype"="otaviocx" \
    "com.trilogy.maintainer.email"="otaviocalaca.xavier@aurea.com" \
    "description"="This file generates a docker image containing MySQL and the deps and tools needed to build and run GLPI project"

ENV DEBIAN_FRONTEND=noninteractive

ADD scripts /opt

# Install dependencies and tools
RUN apt-get update -y && \
    apt-get install -y \
        apache2 \
        cron \
        curl \
        git \
        php \
        php-apcu-bc \
        php-cas \
        php-curl \
        php-gd \
        php-imap \
        php-ldap \
        php-mbstring \
        php-mysql \
        php-xmlrpc \
        php-xml \
        unzip \
        wget \
        zip && \
    apt-get clean all && \
    chmod a+x /opt/*.sh /opt/bin/* && \
    # Install Composer and Atoum as commands
    cd /opt && \
    /opt/install-composer.sh && \
    wget https://github.com/atoum/atoum/releases/download/3.3.0/atoum.phar && \
    mv /opt/bin/* /usr/bin && \
    rm -rf /opt/bin
    
WORKDIR /data

ENTRYPOINT /opt/entrypoint.sh
