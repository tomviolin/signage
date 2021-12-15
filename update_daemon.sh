#!/bin/bash

cd ~/services/signage
while (true); do
	if [ ! -f /tmp/lastdir ]; then
		ls -l > /tmp/lastdir
	fi
	ls -l > /tmp/thisdir
	diff -q /tmp/lastdir /tmp/thisdir || make
	mv /tmp/thisdir /tmp/lastdir
	sleep 1
done


