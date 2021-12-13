#!/bin/bash

#phpcbf
vendor/bin/phpcbf

#methods visibility
sed -E "s/^( +)(final )?(abstract )?(static )?function /\\1public \\2\\3\\4function /g" -i ajax/**/*.php front/**/*.php inc/**/*.php install/**/*.php lib/**/*.php src/**/*.php tests/**/*.php

#properties visibility
sed -E "s/^(   ?)( static )?( var )?\\$/\\1public\\2 \\$/g" -i ajax/**/*.php front/**/*.php inc/**/*.php install/**/*.php lib/**/*.php src/**/*.php tests/**/*.php

#useless comments
sed -E "s/}(( )?\/\/.*)/}/g" -i ajax/**/*.php front/**/*.php inc/**/*.php install/**/*.php lib/**/*.php src/**/*.php tests/**/*.php

#phpcbf
vendor/bin/phpcbf
vendor/bin/phpcs
