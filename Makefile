all: build run
build:
	docker build -t signage .

run:
	docker run -d --restart always -p 8882:80 -v /tmp/roarcalendars:/calendars signage

