#!/bin/bash

units_dir=./tests/units
func_dir=./tests/functionnal

find "$units_dir" -type f -name "*.php" | while read i;do
    filename="$(basename "${i}")"
    find "$func_dir" -type f -name "$filename"
done

find "$func_dir" -type f -name "*.php" | while read i;do
    filename="$(basename "${i}")"
    find "$units_dir" -type f -name "$filename"
done
