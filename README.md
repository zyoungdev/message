# Encrypted CRUD Messaging

This is a messaging service that uses PHP, Javascript, [MongoDB](https://github.com/mongodb/mongo), and [libsodium](https://github.com/jedisct1/libsodium). It has a CRUD workflow so it is basically a single server email application.

Working example at http://messenger.zydev.space

## Features

* Easily host interoffice or private messaging service with confidence your data is secure
* Images and Files are embedded into the plaintext and converted before DB storage
* Secrets are created at login so there is no key management
* Ciphertext is not held on the client. It is only decrypted upon message opening.
* Search for message by "Fingerprint"
* Nest messages into user threads
* Sort messages by Timestamp, Username, or Size
* Quickly send messages by searching their username in the Contact list
* Mobile friendly single page application

## Prerequisites

* Apache server running PHP
* MongoDB
* libsodium 1.0.6
* libsodium-php 1.0.2
* SASS to compile CSS

## Installation

Once you have all of the prerequisites, simply `git clone` this project to your servers public directory. You will need to compile `main.scss` with `sass main.scss` to get your completed CSS template. There is no user verification implemented so simply type in a username and password and a profile is created for you.

## Caveats

* Due to MongoDB's maximum document size, messages have a maximum size of 16MB
* When changing your user password a new secret key is created. Thus, all your previously recieved messages will not be decryptable as the original secret key is lost.
* When changing your display name, only your future messages will reflect the name change
* Usernames can only consist of letters and numbers. They also have a maximum length of 64.
* Passwords can consist of anything with any length.

## Files for editing

* `global-template.scss` is where the main css editing is done
* `phpSrc/` contains all the DB interaction classes
* `index.php` is the main entry point for the application
* `js/index.js` is the main application logic


## Implementation Details

At login, the password is immediately hashed and a secret is derived. A challenge secret is also derived from the password to verify identity whenever interacting with the DB. This gives you the ability to use stronger hashing at login and faster verification when interacting with the database. The public key, secret key, and challenge key are held in $_SESSION variables to encrypt and decrypt messages.

There is a public and private MongoDB Collection. The public collection holds each persons username, public key, last login timestamp, and avatar. The private collection holds the persons username, last login timestamp, hashed password, public key, salt, nonce, encrypted challenge, settings, messages, and contacts.

Each messages details are stored in the users private document. The senders username, display name, public key, and the messages size, nonce, and ID mapping are stored by user and timestamp.

Each message document holds the ID mapping and ciphertext.