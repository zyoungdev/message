var APP = (function()
{
    var
    hf = (function()
    {
        return{
            elCN: function(cn)
            {
                return document.getElementsByClassName(cn);
            },
            isInside: function(node, target)
            {
                for(; node != null; node = node.parentNode)
                {
                    if (node == target) return true;
                }
            },
            rTarget: function(node, target)
            {
                for(; node != null; node = node.parentNode)
                {
                    if (node.className == target) return node;
                }
            },
            cN: function(e,cl)
            {
                return e.className == cl;
            },
            tN: function(e,tag)
            {
                return e.tagName == tag;
            },
            cEL: function(el, attr)
            {
                var node = document.createElement(el);
                if (attr)
                {
                    for (var a in attr)
                    {
                        if (attr.hasOwnProperty(a))
                        {
                            node.setAttribute(a, attr[a]);
                        }
                    }
                }
                if (arguments[2])
                    node.innerHTML = arguments[2];
                return node;
            },
            ajax: function(type, fd, uri, callback)
            {
                var
                xhr = new XMLHttpRequest();

                xhr.onload = function()
                {
                    callback(this.response);
                }
                xhr.open(type, uri, true);
                if (fd) xhr.send(fd);
                else xhr.send();
            },
            convertSize: function(size)
            {
                var s = size;
                s /= 1000;
                if (s > 1000)
                {
                    s /= 1000;
                    s = Math.ceil(s) + " MB";
                }
                else
                {
                    s = Math.ceil(s) + " KB";
                }
                return s;
            },
            convertTime: function(time)
            {
                var
                now = new Date();

                now = now.getTime() / 1000;

                if (now-1440 < time)
                    return time.toLocaleTimeString(); 
                else
                    return time.toLocaleDateString();
            }
        };
    })(),
    contactList = (function()
    {
        var
        contactList,
        sorting = false,
        buildList = function()
        {
            hf.ajax("GET", null, "templates/contactList.php", function(res)
            {
                var
                container = hf.cEL("div", {class: "module-container contact-list-container"}),
                frag = document.createDocumentFragment(),
                containerExists = hf.elCN("contact-list-container")[0];

                container.innerHTML = res;

                if (!containerExists)
                    document.body.appendChild(container);
                else
                    hf.elCN("contact-list")[0].innerHTML = "";
                
                if (contactList["code"] == 0) return;

                for (var user in contactList)
                {
                    var
                    selBut = hf.cEL("input", {class: "contact-checkbox", type: "checkbox"}),
                    contact = hf.cEL("div", {class: "contact"}),
                    username = hf.cEL("div", {class: "contact-username"}, user);
                    // delBut = hf.cEL("button", {class: "contact-delete-button"}, "Delete");

                    contact.appendChild(selBut);
                    contact.appendChild(username);
                    // contact.appendChild(delBut);

                    frag.appendChild(contact);
                }
                hf.elCN("contact-list")[0].appendChild(frag);
            });
        },
        getList = function()
        {
            if (contactList)
            {
                buildList();
                return;
            }
            hf.ajax("GET", null, "phpSrc/listContacts.php", function(res)
            {
                contactList = JSON.parse(res);
                // console.log(contactList);
                sortContacts();
                buildList();
            });
        },
        addContact = function(u)
        {
            if (contactList[u])
            {
                navigation.stateChange("compose");
                messageDraft.init(u);
                return;
            }

            var
            fd = new FormData();

            fd.append("contact", u);

            hf.ajax("POST", fd, "phpSrc/addContact.php", function(res)
            {
                console.log(res);
                hf.ajax("GET", null, "phpSrc/listContacts.php", function(r)
                {
                    contactList = JSON.parse(r);
                    if (contactList["code"] == null)
                    {
                        console.log(contactList);
                        sorting = false;
                        sortContacts();
                        buildList();
                    }
                });
            });
        },
        deleteContact = function(i, u)
        {
            var
            fd = new FormData();

            fd.append("username", u);

            hf.ajax("POST", fd, "phpSrc/deleteContact.php", function(res)
            {
                // console.log(res);
                // res = JSON.parse(res);
                if (res["code"] == null)
                {
                    delete contactList[u];
                    buildList();
                }
                else
                {
                    // console.log(res);
                }
            });
        },
        deleteMultipleContacts = function()
        {
            // console.log(contactList);
            var
            newContactList = {},
            fd = new FormData(),
            backupContactList = contactList,
            checkboxes = hf.elCN("contact-checkbox"),
            delBut = hf.elCN("delete-multiple-contact-button")[0],
            contactListContainer = hf.elCN("contact-list")[0];

            checkBoxIndexes = [];
            for (var i = 0, len = checkboxes.length; i < len; i++)
            {
                if (!checkboxes[i].checked)
                {
                    var
                    user = checkboxes[i].parentNode.children[1].innerText;
                    newContactList[user] = contactList[user];
                }
            }
            contactList = newContactList;
            fd.append("contacts", JSON.stringify(contactList));
            hf.ajax("POST", fd, "phpSrc/deleteMultipleContacts.php", function(res)
            {
                console.log(res);
                res = JSON.parse(res);

                if (res["code"] == 0)
                {
                    contactList = backupContactList;
                }
                delBut.style.display = "none";
                buildList();
            });
        },
        selectAll = function(e)
        {
            var checkboxes = hf.elCN("contact-checkbox"),
            delBut = hf.elCN("delete-multiple-contact-button")[0];

            if (e.checked)
            {
                delBut.style.display = "block";
                for (var i = 0, len = checkboxes.length; i < len; i++)
                {
                    checkboxes[i].checked = true;
                }
            }
            else
            {
                for (var i = 0, len = checkboxes.length; i < len; i++)
                {
                    checkboxes[i].checked = false;
                }
                delBut.style.display = "none";
            }
        },
        sortContacts = function()
        {
            var
            newContactList = {},
            keys = [];

            for (var key in contactList)
            {
                if (contactList.hasOwnProperty(key))
                    keys.push(key);
            }
            keys.sort();

            if (sorting)
                keys.reverse();
            for (var i = 0, len = keys.length; i < len; i++)
            {
                newContactList[keys[i]] = contactList[keys[i]];
            }
            contactList = newContactList;
            sorting = !sorting;
        },
        checkboxClick = function(e)
        {
            var
            selectAllCheck = hf.elCN("contact-list-heading-checkbox")[0],
            checkboxes = hf.elCN("contact-checkbox"),
            delBut = hf.elCN("delete-multiple-contact-button")[0];
            // console.log(delBut);

            //if any are selected view delete button
            for (var i = 0, len = checkboxes.length; i < len; i++)
            {
                if (checkboxes[i].checked)
                {
                    delBut.style.display = "block";
                    return;
                }
            }
            selectAllCheck.checked = false;
            delBut.style.display = "none";
        }
        return {
            init: function()
            {
                getList();
            },
            click: function(ev)
            {
                var
                e = ev.target,
                contactListContainer = hf.elCN("contact-list-container")[0];

                if (hf.isInside(e, contactListContainer))
                {
                    if (hf.cN(e, "contact-username"))
                    {
                        var user = ev.target.innerText;

                        navigation.stateChange("compose");
                        messageDraft.init(user);
                    }
                    else if (hf.cN(e, "add-contact-button"))
                    {
                        var
                        user = hf.elCN("add-contact-input")[0].value;
                        addContact(user)
                    }
                    else if (hf.cN(e, "contact-list-heading-checkbox"))
                    {
                        selectAll(e);
                    }
                    else if (hf.cN(e, "contact-list-heading-username"))
                    {
                        sortContacts();
                        buildList();
                    }
                    else if (hf.cN(e, "contact-checkbox"))
                    {
                        checkboxClick(e);
                    }
                    else if (hf.cN(e, "delete-multiple-contact-button"))
                    {
                        deleteMultipleContacts();
                    }
                }
            }
        };
    })(),
    messageDraft = (function()
    {
        var
        imgs = [],
        fls = [],
        fileList = [],
        imgList = [],
        clearFiles = function()
        {
            imgs = [];
            fls = [];
            fileList = [];
            imgList = [];
        }
        sendPlaintext = function(rec, pt, el)
        {
            // console.log(fls);
            if (imgs.length > 0)
            {
                pt += "<div class=image-file-list>";
                pt += JSON.stringify(imgList);
                pt += "</div>";

                pt += "<div class=view-message-images-container>";
                for (var i = 0, len = imgs.length; i < len; i++)
                {
                    pt += imgs[i];
                }
                pt += "</div>";
            }
            if (fls.length > 0)
            {
                pt += "<div class=file-file-list>";
                pt += JSON.stringify(fileList);
                pt += "</div>";

                pt += "<div class=view-message-files-container>";
                for (var i = 0, len = fls.length; i < len; i++)
                {
                    pt += fls[i];
                }
                pt += "</div>";
            }
            // console.log(pt);
            if (pt === "") return;
            var
            fd = new FormData();

            fd.append("plaintext", pt);
            fd.append("recipient", rec);

            var size = pt.length;
            for (var i=pt.length-1; i >= 0; i--) 
            {
                var code = pt.charCodeAt(i);
                if (code > 0x7f && code <= 0x7ff) size++;
                else if (code > 0x7ff && code <= 0xffff) size+=2;
                if (code >= 0xDC00 && code <= 0xDFFF) i--;
            }

            fd.append("messageSize", size);

            hf.ajax("POST", fd, "phpSrc/sendMessage.php", function(res)
            {
                // console.log("Response recieved for sent message");
                document.body.removeChild(el);
                imgs = [];
                contactList.init();
            });
        },
        addFiles = function()
        {
            var
            fileInput = hf.elCN("file-upload-input")[0];
            fileInput.click();

            fileInput.onchange = function()
            {
                var
                files = fileInput.files;

                for (var i = 0, len = files.length; i < len; i++)
                {
                    if (files[i].type.match("image.*"))
                    {
                        imgList.push(files[i]);

                        var
                        img = new Image(),
                        reader = new FileReader();
                        img.file = files[i];

                        reader.onload = (function(aImg) 
                        { 
                            return function(e) 
                            { 
                                aImg.src = e.target.result; 

                                var
                                i = "<div class=img-container><div class=img style=background-image:url(";
                                i += e.target.result;
                                i += ");><div><p>Download</p></div></div></div>";

                                imgs.push(i);
                            }; 
                        })(img);
                        reader.readAsDataURL(files[i]);
                    }
                    else
                    {
                        fileList.push(files[i]);

                        var
                        reader = new FileReader();

                        reader.onload = (function(file)
                        {
                            return function(res)
                            {
                                var
                                f = "<div class=fl-container><a target=_blank href=";
                                f += res.target.result;
                                f += "><p>";
                                f += file.name + "<br>";

                                if (file.size > 10000)
                                {
                                    f += (file.size / 1000000).toFixed(2) + "MB";
                                }
                                else
                                {
                                    f += (file.size / 1000).toFixed(2) + "KB";
                                }

                                f += "</p></a></div>";

                                fls.push(f);
                            }
                        })(files[i]);
                        reader.readAsDataURL(files[i]);
                    }
                }
            }
        },
        closeMessage = function(e)
        {
            for (var i = 0, len = imgs.length; i < len; i++)
            {
                imgs[i] = "0".repeat(imgs[i].length);
            }
            document.body.removeChild(e);
        }
        return{
            init: function(rec)
            {
                hf.ajax("GET", null, "templates/sendMessage.php", function(res)
                {
                    var
                    container = document.createElement("div");
                    container.className = "module-container send-message-box";
                    container.innerHTML = "";

                    var elemExists = hf.elCN("send-message-box")[0]
                    if (!elemExists)
                        document.body.appendChild(container);
                    container.innerHTML = res;

                    hf.elCN("send-message-button").disabled = false;
                    if (rec)
                        hf.elCN("send-message-box")[0].children[0].value = rec;
                });
            },
            click: function(ev)
            {
                var
                e = ev.target,
                sendMessageBox = hf.elCN("send-message-box")[0];

                if (hf.isInside(e, sendMessageBox))
                {
                    var
                    rec = sendMessageBox.getElementsByTagName("input")[0],
                    ta = sendMessageBox.getElementsByTagName("textarea")[0],
                    file = sendMessageBox.getElementsByTagName("button")[0],
                    sub = sendMessageBox.getElementsByTagName("button")[1],
                    dis = sendMessageBox.getElementsByTagName("button")[2];
                    if (e == ta)
                    {

                    }
                    else if (e == file)
                    {
                        addFiles();
                    }
                    else if (e == sub)
                    {
                        e.disabled = true;
                        sendPlaintext(rec.value, ta.value,e.parentNode);
                        clearFiles();
                    }
                    else if (e == dis)
                    {
                        closeMessage(e.parentNode);
                        clearFiles();
                        navigation.stateChange("messages");
                    }
                }
            }
        };
    })(),
    messageView = (function()
    {
        var
        currentMessage,
        flFileList,
        imgFileList;
        buildView = function()
        {
            hf.ajax("GET", null, "templates/viewMessage.php", function(res)
            {
                var
                container = hf.cEL("div", {class: "module-container view-message-container"}),
                frag = document.createDocumentFragment(),
                s = currentMessage["size"],
                date = new Date(currentMessage["timestamp"] * 1000),
                containerExists = hf.elCN("view-message-container")[0];

                container.innerHTML = res;

                if (!containerExists)
                {
                    document.body.appendChild(container);
                }
                else
                {
                    sender.innerHTML = "";
                    timestamp.innerHTML = "";
                    message.innerHTML = "";
                }
                
                if (contactList["code"] == 0) return;

                var
                sender = hf.elCN("view-message-sender")[0],
                timestamp = hf.elCN("view-message-timestamp")[0],
                size = hf.elCN("view-message-size")[0],
                message = hf.elCN("view-message-message")[0];

                sender.innerHTML = currentMessage["sender"];
                timestamp.innerHTML = date.toLocaleString();
                size.innerHTML = hf.convertSize(currentMessage["size"]);
                message.innerHTML = currentMessage["plaintext"];

                var
                imgFileListContainer = hf.elCN("image-file-list")[0],
                flFileListContainer = hf.elCN("file-file-list")[0];
                if (imgFileListContainer)
                    imgFileList = JSON.parse(imgFileListContainer.innerText);
                if (flFileListContainer)
                    flFileList = JSON.parse(flFileListContainer.innerText);
            });
        },
        deleteMessage = function(e)
        {
            messageList.deleteMessage(currentMessage["sender"], currentMessage["timestamp"]);
        },
        closeMessage = function(e)
        {
            // document.body.removeChild(e.parentNode.parentNode);
            
            //Clear the plaintext from memory, A little heavy handed
            // if (currentMessage)
                // currentMessage["plaintext"] = "0".repeat(currentMessage["plaintext"].length);
            currentMessage = {};
        },
        imageClick = function(img)
        {
            var
            anchor = hf.cEL("a", {target: "_blank"}),
            style = window.getComputedStyle(img.children[0]).backgroundImage,
            uri = style.slice(4, style.length-1);
            container = hf.elCN("view-message-images-container")[0],
            index = Array.prototype.indexOf.call(container.children, img);

            anchor.download = imgFileList[index].name;
            anchor.href = uri;
            anchor.click();
        },
        fileClick = function(img)
        {
            // console.log(flFileList);
            var
            fileContainer = hf.elCN("view-message-files-container")[0],
            index = Array.prototype.indexOf.call(fileContainer.children, img.parentNode);

            img.download = flFileList[index].name;
            img.click();
        }
        return{
            init: function(res)
            {
                currentMessage = res;
                buildView();
            },
            click: function(ev)
            {
                var
                e = ev.target,
                viewMessageContainer = hf.elCN("view-message-container")[0],
                fileContainer = hf.elCN("view-message-files-container")[0],
                imgContainer = hf.elCN("view-message-images-container")[0];

                if (hf.isInside(e, viewMessageContainer))
                {
                    if (hf.cN(e, "view-message-reply-button"))
                    {
                        messageDraft.init(currentMessage["sender"]);
                    }
                    else if (hf.cN(e, "view-message-delete-button"))
                    {
                        deleteMessage(e);
                        closeMessage(e);
                        navigation.stateChange("messages");
                    }
                    else if (hf.cN(e, "view-message-close-button"))
                    {
                        closeMessage(e);
                        navigation.stateChange("messages");
                    }
                    else if (hf.isInside(e, imgContainer))
                    {
                        ev.preventDefault();
                        var img;
                        if (img = hf.rTarget(e, "img-container"))
                        {
                            imageClick(img);
                        }
                    }
                    else if (hf.isInside(e, fileContainer))
                    {
                        ev.preventDefault();
                        var img;
                        if (img = hf.rTarget(e, "fl-container"))
                        {
                            fileClick(img.children[0]);
                        }
                    }
                }
            }
        }
    })(),
    messageList = (function()
    {
        var
        messageList,
        buildList = function()
        {
            hf.ajax("GET", null, "templates/messageList.php", function(res)
            {
                var
                container = hf.cEL("div", {class: "module-container message-list-container"}),
                frag = document.createDocumentFragment(),
                containerExists = hf.elCN("message-list-container")[0];

                container.innerHTML = res;

                if (!containerExists)
                    document.body.appendChild(container);
                else
                    hf.elCN("message-list")[0].innerHTML = "";
                
                if (messageList["code"] == 0) return;

                for (var user in messageList)
                {
                    for (var message in messageList[user])
                    {
                        var
                        msg = hf.cEL("div", {class: "message"}),
                        check = hf.cEL("input", {class: "message-checkbox", type: "checkbox"}),
                        username = hf.cEL("div", {class: "message-username"}, user),
                        size = hf.cEL("div", {class: "message-size"}, hf.convertSize(messageList[user][message]["size"])),
                        date = new Date(messageList[user][message].timestamp * 1000),
                        timestamp = hf.cEL("div", {class: "message-timestamp"}, hf.convertTime(date));
                        // delBut = hf.cEL("button", {class: "message-delete-button"}, "Delete");

                        timestamp.dataset.timestamp = messageList[user][message].timestamp;

                        msg.appendChild(check);
                        msg.appendChild(size);
                        msg.appendChild(username);
                        msg.appendChild(timestamp);
                        // msg.appendChild(delBut);

                        frag.appendChild(msg);
                    }
                }
                hf.elCN("message-list")[0].appendChild(frag);
            });
        },
        viewMessage = function(u,t)
        {
            var
            fd = new FormData();

            fd.append("username", u);
            fd.append("timestamp", t);

            hf.ajax("POST", fd, "phpSrc/viewMessage.php", function(res)
            {
                res = JSON.parse(res);

                messageView.init(res);                
            });
        },
        checkboxClick = function(e)
        {
            var
            selectAllCheck = hf.elCN("message-list-heading-checkbox")[0],
            checkboxes = hf.elCN("message-checkbox"),
            delBut = hf.elCN("delete-multiple-messages-button")[0];

            //if any are selected view delete button
            for (var i = 0, len = checkboxes.length; i < len; i++)
            {
                if (checkboxes[i].checked)
                {
                    delBut.style.display = "block";
                    return;
                }
            }
            selectAllCheck.checked = false;
            delBut.style.display = "none";
        },
        selectAll = function(e)
        {
            var checkboxes = hf.elCN("message-checkbox"),
            delBut = hf.elCN("delete-multiple-messages-button")[0];

            if (e.checked)
            {
                delBut.style.display = "block";
                for (var i = 0, len = checkboxes.length; i < len; i++)
                {
                    checkboxes[i].checked = true;
                }
            }
            else
            {
                for (var i = 0, len = checkboxes.length; i < len; i++)
                {
                    checkboxes[i].checked = false;
                }
                delBut.style.display = "none";
            }
        },
        delMessage = function(u, t)
        {
            var
            fd = new FormData();

            fd.append("timestamp", t);
            fd.append("username", u);

            hf.ajax("POST", fd, "phpSrc/deleteMessage.php", function(res)
            {
                // console.log(res);
                // res = JSON.parse(res);
                if (res["code"] == null)
                {
                    delete messageList[u][t];
                    buildList();
                }
                else
                {
                    // console.log(res);
                }
            });
        },
        deleteMultipleMessages = function()
        {
            var
            newMessageList = messageList,
            deleteMessages = [],
            fd = new FormData(),
            backupMessageList = messageList,
            checkboxes = hf.elCN("message-checkbox"),
            delBut = hf.elCN("delete-multiple-messages-button")[0],
            messageListContainer = hf.elCN("message-list")[0];

            checkBoxIndexes = [];
            for (var i = 0, len = checkboxes.length; i < len; i++)
            {
                if (checkboxes[i].checked)
                {
                    var
                    user = checkboxes[i].parentNode.children[2].innerText,
                    timestamp = checkboxes[i].parentNode.children[3].dataset.timestamp;
                    
                    // console.log(newMessageList);
                    deleteMessages.push(newMessageList[user][timestamp]["id"]);
                    delete newMessageList[user][timestamp];
                }
            }
            for (var user in newMessageList)
            {
                if (newMessageList[user].length == 0)
                {
                    delete newMessageList[user];
                }
            }

            messageList = newMessageList;
            // console.log(messageList);
            fd.append("messages", JSON.stringify(messageList));
            fd.append("deleteMessages", JSON.stringify(deleteMessages));
            hf.ajax("POST", fd, "phpSrc/deleteMultipleMessages.php", function(res)
            {
                // console.log(res);
                res = JSON.parse(res);

                if (res["code"] == 0)
                {
                    messageList = backupMessageList;
                }
                delBut.style.display = "none";
                buildList();
            });
        },
        getList = function()
        {
            hf.ajax("GET", null, "phpSrc/listMessages.php", function(res)
            {
                messageList = JSON.parse(res);
                console.log(messageList);
                buildList();
            });
        };

        return{
            init: function()
            {
                getList();
            },
            deleteMessage: function(user,time)
            {
                delMessage(user,time);
            },
            click: function(ev)
            {
                var
                e = ev.target,
                messageListContainer = hf.elCN("message-list-container")[0];

                if (hf.isInside(e, messageListContainer))
                {
                    if (hf.cN(e, "message-username") || hf.cN(e, "message-timestamp") || hf.cN(e, "message-timestamp"))
                    {
                        var
                        user = e.parentNode.children[2].innerText,
                        time = e.parentNode.children[3].dataset.timestamp;

                        navigation.stateChange("viewMessage");
                        viewMessage(user, time);
                    }
                    else if (hf.cN(e, "refresh-messages-button"))
                    {
                        getList();
                    }
                    else if (hf.cN(e, "create-message-button"))
                    {
                        messageDraft.init();
                    }
                    else if (hf.cN(e, "message-list-heading-checkbox"))
                    {
                        selectAll(e);
                    }
                    else if (hf.cN(e, "message-checkbox"))
                    {
                        checkboxClick();
                    }
                    else if (hf.cN(e, "delete-multiple-messages-button"))
                    {
                        deleteMultipleMessages();
                    }
                }
            }
        }
    })(),
    settings = (function()
    {
        return {
            init: function()
            {

            }
        }
    })(),
    navigation = (function()
    {
        var
        buildNavigation = function(res)
        {
            var
            container = hf.cEL("div", {class: "navigation-container"});
            container.innerHTML = res;
            document.body.appendChild(container);
        },
        getTemplate = function()
        {
            hf.ajax("GET", null, "templates/navigation.php", function(res){
                buildNavigation(res);
                changeState("messages", hf.elCN("nav-messages")[0]);
            })
        },
        setState = function()
        {
            var
            body = document.body,
            nav = hf.elCN("navigation-container")[0];

            (function()
            {
                for (var i = 1, len = body.children.length; i < len; i++)
                {
                    body.removeChild(body.children[i]);
                }
            })();

            (function()
            {
                for (var i = 0, len = nav.children.length; i < len; i++)
                {
                    nav.children[i].classList.remove("active");
                }
            })();

        },
        changeState = function(state)
        {
            switch (state)
            {
                case "compose":
                {
                    setState();
                    hf.elCN("nav-compose")[0].classList.add("active");
                    messageDraft.init();
                    break;
                }
                case "messages":
                {
                    setState();
                    hf.elCN("nav-messages")[0].classList.add("active");
                    messageList.init();
                    break;
                }
                case "contacts":
                {
                    setState();
                    hf.elCN("nav-contacts")[0].classList.add("active");
                    contactList.init();
                    break;
                }
                case "settings":
                {
                    setState();
                    hf.elCN("nav-settings")[0].classList.add("active");
                    settings.init();
                    break;
                }
                case "viewMessage":
                {
                    setState();
                    break;
                }
            }
        };
        return {
            init: function()
            {
                getTemplate();
            },
            stateChange: function(state)
            {
                changeState(state);
            },
            click: function(ev)
            {
                var
                e = ev.target,
                clicked,
                navContainer = hf.elCN("navigation-container")[0];

                if (hf.isInside(e, navContainer))
                {
                    if (clicked = hf.rTarget(e, "nav-compose"))
                    {
                        changeState("compose");
                    }
                    else if (clicked = hf.rTarget(e, "nav-messages"))
                    {
                        changeState("messages");
                    }
                    else if (clicked = hf.rTarget(e, "nav-contacts"))
                    {
                        changeState("contacts");
                    }
                    else if (clicked = hf.rTarget(e, "nav-settings"))
                    {
                        changeState("settings");
                    }
                }
            }
        }
    })(),
    login = (function()
    {
        var
        submitCreds = function(un, pw)
        {
            if (un === "") return;
            if (pw === "") return;

            //send creds to phpSrc/login.php
            var
            fd = new FormData();

            fd.append("username", un);
            fd.append("password", pw);

            hf.ajax("POST", fd, "phpSrc/login.php", function(res)
            {
                if (res)
                {
                    var
                    r = JSON.parse(res);
                    if (r.code)
                    {
                        //Logged in!
                        // console.log(r.message);
                        hf.elCN("loginerror")[0].innerText = r.message;

                        document.body.innerHTML = "";
                        // sendMessage.init();
                        // messageList.init();
                        // contactList.init();
                        navigation.init();

                    }
                    else
                    {
                        //Something failed
                        // console.log(r.message);
                        hf.elCN("loginerror")[0].innerText = r.message;
                    }
                }
            })
            


        }
        return{
            click: function(ev)
            {
                var
                e = ev.target,
                loginBox = hf.elCN("login-box")[0];

                if (hf.isInside(e, loginBox))
                {
                    var
                    un = loginBox.getElementsByTagName("input")[0],
                    pw = loginBox.getElementsByTagName("input")[1],
                    sub = loginBox.getElementsByTagName("button")[0];
                    if (e == un)
                    {

                    }
                    else if (e == pw)
                    {

                    }
                    else if (e == sub)
                    {
                        submitCreds(un.value, pw.value);
                    }
                }
            }
        };
    })();
    return {
        clicked: function(ev)
        {
            login.click(ev);
            navigation.click(ev);
            messageDraft.click(ev);
            messageList.click(ev);
            contactList.click(ev);
            messageView.click(ev);
        }
    };
})();

window.onclick = function(ev)
{
    APP.clicked(ev);
}