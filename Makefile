
builddockerimage:
	docker build -t signage .

rundockerimage:
	docker run -i --rm -p 8880:80 -v /tmp/roarcalendars:/calendars signage

