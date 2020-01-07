FROM ubuntu:16.04

# install core
RUN apt-get update && apt-get -y install \
    software-properties-common \
    curl \
    build-essential libssl-dev \
    apt-transport-https

# install necessary locales
RUN apt-get install -y locales \
    && echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen

# install php
RUN LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php
RUN apt-get update && apt-get install \
    php7.3 \
    php7.3-dev \
    php7.3-xml \
    php7.3-curl \
    php7.3-common \
    php7.3-json \
    php7.3-mbstring \
    php7.3-odbc \
    php7.3-mysql \
    php7.3-zip \
    -y --allow-unauthenticated

# install mysql odbc driver
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - && \
    curl https://packages.microsoft.com/config/ubuntu/16.04/prod.list > /etc/apt/sources.list.d/mssql-release.list

RUN apt-get update && ACCEPT_EULA=Y apt-get -y install msodbcsql17 mssql-tools mssql-cli

RUN echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bash_profile && \
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc

RUN ["/bin/bash", "-c", "source ~/.profile"]

RUN apt-get -y install unixodbc-dev

# install sqlsrv php extensions
RUN pecl channel-update pecl.php.net
RUN pecl install sqlsrv pdo_sqlsrv
RUN echo extension=pdo_sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/30-pdo_sqlsrv.ini
RUN echo extension=sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/20-sqlsrv.ini

# finalize
WORKDIR /var/www

# leave box available
CMD ["sleep", "99999"]
