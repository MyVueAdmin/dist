# :heavy_check_mark: MyVueAdmin
MyVueAdmin (MVA) is a web application for MySQL-databases management. It is created to be fast and deliver "nice-to-use" experience. This repo contains compiled versions of MyVueAdmin.

# :computer: Requirements
## Server side
PHP with OpenSSL. PHP7 is recommended but not required.
## Client side
Any modern web-browser with JavaScript, cookies and local storage support.

# :floppy_disk: Installation
## Step 1 - download or clone
Download files from this repository or just clone it to to any location on your web-server, it may be root folder of your web server or any subfolder.
## Step 2 - not required but very important
Change security setting in folder "backend/config.ini". It has description of each parameter inside.
## Step 3 - use it
MyVueAdmin will be available in its folder URL through web-browser.

# :information_source: More info: backend and frontend
MVA consists of two parts - backend and frontend. Backend part is a simple REST-application written in PHP, meanwhile all logics and interesting stuff are in frontend part. MVA frontend is based on Vue.js (this is where "Vue" part comes) framework and works as SPA (single page application).

# :page_facing_up: Source code
See [MyVueAdmin/frontend](https://github.com/MyVueAdmin/frontend) and [MyVueAdmin/backend](https://github.com/MyVueAdmin/backend).

# :pill: Try it
Demo version: <a href="http://mva-demo.herokuapp.com" target="_blank">mva-demo.herokuapp.com</a>  
Username/password: demo/demo  


