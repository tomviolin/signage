all: build run
build:
	docker build -t signage .

run:
	docker kill signage || echo ""
	docker rm signage || echo ""
	docker run --name signage -d --restart always -p 80:80 -v /tmp/roarcalendars:/calendars signage

