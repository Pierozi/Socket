build:
	openssl req -nodes -x509 -newkey rsa:4096 -keyout key.pem -out cert.crt -days 365
	cat cert.crt key.pem >> cert.pem
