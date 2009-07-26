#!/bin/bash
for i in $(grep -rli $'\xEF\xBB\xBF' *); do
    echo Processing $i;
    cat $i | perl -pe 's/\xEF\xBB\xBF//i' > $i.new;
    mv $i.new $i;
done
