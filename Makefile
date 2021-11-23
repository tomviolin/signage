
builddockerimage:
	docker build -t signage .

rundockerimage:
	docker run -it --rm -p 8882:80 -v /home/tomh/stuff:/calendars signage

