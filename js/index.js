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
            },
            getAvatar: function(usr, callback)
            {
                var fd = new FormData();

                fd.append("user", usr);

                hf.ajax("POST", fd, "phpSrc/getAvatar.php", callback);
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

                    contact.appendChild(selBut);
                    contact.appendChild(username);

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
                console.log(contactList);
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

            var fd = new FormData();
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
            var fd = new FormData();
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
                    checkboxes[i].checked = true;
            }
            else
            {
                for (var i = 0, len = checkboxes.length; i < len; i++)
                    checkboxes[i].checked = false;

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
                newContactList[keys[i]] = contactList[keys[i]];

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
                        var user = hf.elCN("add-contact-input")[0].value;
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
                    pt += imgs[i];

                pt += "</div>";
            }
            if (fls.length > 0)
            {
                pt += "<div class=file-file-list>";
                pt += JSON.stringify(fileList);
                pt += "</div>";

                pt += "<div class=view-message-files-container>";

                for (var i = 0, len = fls.length; i < len; i++)
                    pt += fls[i];

                pt += "</div>";
            }
            // console.log(pt);
            if (pt === "") return;

            var 
            fd = new FormData(),
            size = pt.length;

            fd.append("plaintext", pt);
            fd.append("recipient", rec);

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
                // console.log(res);
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
                files = fileInput.files,
                sendMessageBox = hf.elCN("send-message-box")[0],
                imageStage = hf.cEL("div", {class: "image-stage"});
                fileStage = hf.cEL("div", {class: "file-stage"});

                sendMessageBox.appendChild(imageStage);
                sendMessageBox.appendChild(fileStage);

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
                                console.log(e.target.result);
                                var
                                i = "<div class=img-container><div class=img style=background-image:url(";
                                i += e.target.result;
                                i += ");><div><p>Download</p></div></div></div>";

                                imgs.push(i);

                                //Add images to stage
                                var
                                ii = "<div class=img-container><div class=img style=background-image:url(";
                                ii += e.target.result;
                                ii += ");><div><p>Delete</p></div></div></div>";

                                imageStage.innerHTML += ii;
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
                                f += "</p><p>Download</p></a></div>";

                                var
                                ff = "<div class=fl-container><a target=_blank href=";
                                ff += res.target.result;
                                ff += "><p>";
                                ff += file.name + "<br>";

                                if (file.size > 10000)
                                {
                                    ff += (file.size / 1000000).toFixed(2) + "MB";
                                }
                                else
                                {
                                    ff += (file.size / 1000).toFixed(2) + "KB";
                                }
                                ff += "</p><p>Delete</p></a></div>";

                                fls.push(f);
                                fileStage.innerHTML += ff;
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
                imgs[i] = "0".repeat(imgs[i].length);

            document.body.removeChild(e);
        },
        imageClickStage = function(img)
        {
            var
            container = hf.elCN("image-stage")[0],
            index = Array.prototype.indexOf.call(container.children, img);

            imgs.splice(index, 1);
            imgList.splice(index, 1);
            container.removeChild(container.children[index]);
        },
        fileClickStage = function(file)
        {
            var
            container = hf.elCN("file-stage")[0],
            index = Array.prototype.indexOf.call(container.children, file);

            fls.splice(index, 1);
            fileList.splice(index, 1);
            container.removeChild(container.children[index]);
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
                    dis = sendMessageBox.getElementsByTagName("button")[2],
                    fileContainer = hf.elCN("file-stage")[0],
                    imgContainer = hf.elCN("image-stage")[0];
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
                    else if (hf.isInside(e, imgContainer))
                    {
                        ev.preventDefault();
                        var img;
                        if (img = hf.rTarget(e, "img-container"))
                        {
                            console.log("message draft");
                            imageClickStage(img);
                        }
                    }
                    else if (hf.isInside(e, fileContainer))
                    {
                        ev.preventDefault();
                        var file;
                        if (file = hf.rTarget(e, "fl-container"))
                        {
                            fileClickStage(file.children[0]);
                        }
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
        fileClick = function(file)
        {
            // console.log(flFileList);
            var
            fileContainer = hf.elCN("view-message-files-container")[0],
            index = Array.prototype.indexOf.call(fileContainer.children, file.parentNode);

            file.download = flFileList[index].name;
            file.click();
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
                            console.log("view-message");
                            imageClick(img);
                        }
                    }
                    else if (hf.isInside(e, fileContainer))
                    {
                        ev.preventDefault();
                        var file;
                        if (file = hf.rTarget(e, "fl-container"))
                        {
                            fileClick(file.children[0]);
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
        timestamps = [],
        sizes = [],
        users = [],
        timeSorting = true,
        sizeSorting = true,
        userSorting = true,
        sortType = "time",
        currentPage = 0,
        buildItem = function(u, t)
        {
            var
            msg = hf.cEL("div", {class: "message"}),
            check = hf.cEL("input", {class: "message-checkbox", type: "checkbox"}),
            username = hf.cEL("div", {class: "message-username"}, u),
            size = hf.cEL("div", {class: "message-size"}, hf.convertSize(messageList[u][t]["size"])),
            date = new Date(messageList[u][t].timestamp * 1000),
            timestamp = hf.cEL("div", {class: "message-timestamp"}, hf.convertTime(date));

            timestamp.dataset.timestamp = messageList[u][t].timestamp;

            msg.appendChild(check);
            msg.appendChild(size);
            msg.appendChild(username);
            msg.appendChild(timestamp);

            return msg;
        }
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

                var 
                i = Math.floor(currentPage*settings.mNum),
                count = 0;

                if (sortType == "time")
                {
                    for (var len = timestamps.length; i < len; i++)
                    {
                        if (count >= settings.mNum)
                            break;
                        for (var user in messageList)
                        {
                            if (!messageList[user][timestamps[i]])
                                continue;
                            frag.appendChild(buildItem(user, timestamps[i]));
                            count++;
                        }
                    }
                }
                else if (sortType == "size")
                {
                    for (var len = sizes.length; i < len; i++)
                    {
                        if (count >= settings.mNum)
                            break;
                        for (var user in messageList)
                        {
                            for (var time in messageList[user])
                            {   
                                if (messageList[user][time]["size"] == sizes[i])
                                {
                                    frag.appendChild(buildItem(user, time));
                                    count++;
                                }
                            }
                        }
                    }
                }
                else if (sortType == "user")
                {
                    console.log("user", i, currentPage);
                    for (var len = users.length; i < len; i++)
                    {
                        if (count >= settings.mNum)
                            break;
                        for (var u in messageList)
                        {
                            for (var t in messageList[u])
                            {
                                if (messageList[u][t] == users[i])
                                {
                                    if (count >= settings.mNum)
                                        break;
                                    frag.appendChild(buildItem(u, t));
                                    count++;
                                }
                            }
                        }   
                    }
                }
                hf.elCN("message-list")[0].appendChild(frag);

                if (currentPage == 0)
                    hf.elCN("prev")[0].style.visibility = "hidden";
                else
                    hf.elCN("prev")[0].style.visibility = "visible";
                if (currentPage == Math.ceil(timestamps.length / settings.mNum)-1)
                    hf.elCN("next")[0].style.visibility = "hidden";
                else
                    hf.elCN("next")[0].style.visibility = "visible";

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
                // console.log(res);
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
                    checkboxes[i].checked = true;
            }
            else
            {
                for (var i = 0, len = checkboxes.length; i < len; i++)
                    checkboxes[i].checked = false;

                delBut.style.display = "none";
            }
        },
        delMessage = function(u, t)
        {
            var fd = new FormData();

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
                    delete newMessageList[user];
            }

            messageList = newMessageList;

            fd.append("messages", JSON.stringify(messageList));
            fd.append("deleteMessages", JSON.stringify(deleteMessages));
            hf.ajax("POST", fd, "phpSrc/deleteMultipleMessages.php", function(res)
            {
                // console.log(res);
                res = JSON.parse(res);

                if (res["code"] == 0)
                    messageList = backupMessageList;

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
                sortMessageListTimestamp();
                buildList();
            });
        },
        sortMessageListTimestamp = function()
        {
            timestamps = [];
            for (var user in messageList)
                for (var time in messageList[user])
                    timestamps.push(time);

            timestamps.sort();

            if (timeSorting)
                timestamps.reverse();

            timeSorting = !timeSorting;
            sizeSorting = true;
            userSorting = true;
            sortType = "time";
        },
        sortMessageListSize = function()
        {
            sizes = [];
            for (var user in messageList)
                for(var time in messageList[user])
                    sizes.push(parseInt(messageList[user][time]["size"]));

            sizes.sort(function(a, b)
            {
                return a-b;
            });

            if (sizeSorting)
                sizes.reverse();

            sizeSorting = !sizeSorting;
            timeSorting = true;
            userSorting = true;
            currentPage = 0;
            sortType = "size";
        },
        sortMessageListUser = function()
        {
            users = [];
            for (var user in messageList)
                users.push(user);

            users.sort();

            if (userSorting)
                users.reverse();

            var tempUsers = [];
            for (var i = 0, len = users.length; i < len; i++)
            {
                for (var time in messageList[users[i]])
                    tempUsers.push(messageList[users[i]][time]);
            }
            users = tempUsers;

            userSorting = !userSorting;
            timeSorting = true;
            sizeSorting = true;
            sortType = "user";
        },
        turnPage = function(dir)
        {
            var 
            items = timestamps.length,
            maxPages = Math.ceil(items / settings.mNum),
            prev = hf.elCN("prev")[0],
            next = hf.elCN("next")[0];

            if (dir == "prev")
            {
                currentPage--;
                if (currentPage < 0)
                {
                    currentPage = 0;
                    return;
                }
            }
            else if (dir == "next")
            {
                currentPage++;
                if (currentPage >= maxPages)
                {
                    currentPage = maxPages-1;
                    return;
                }
            }
        }

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
                        sortMessageListTimestamp();
                        buildList();
                    }
                    else if (hf.cN(e, "message-list-timestamp"))
                    {
                        sortMessageListTimestamp();
                        buildList();
                    }
                    else if (hf.cN(e, "message-list-username"))
                    {
                        sortMessageListUser();
                        buildList();
                    }
                    else if (hf.cN(e, "message-list-size"))
                    {
                        sortMessageListSize();
                        buildList();
                    }
                    else if (hf.cN(e, "prev"))
                    {
                        turnPage("prev")
                        buildList();
                    }
                    else if (hf.cN(e, "next"))
                    {
                        turnPage("next");
                        buildList();
                    }
                }
            }
        }
    })(),
    settings = (function()
    {
        var
        getTemplate = function()
        {
            hf.ajax("GET", null, "templates/settings.php", function(res)
            {
                var
                container = hf.cEL("div", {class: "module-container settings-container"}),
                containerExists = hf.elCN("settings-container")[0];

                container.innerHTML = res;

                if (!containerExists)
                    document.body.appendChild(container);

                var
                avatar = hf.elCN("avatar-container")[0].children[0],
                username = hf.elCN("settings-username")[0],
                messageNum = hf.elCN("mPerPage")[0];

                avatar.style.backgroundImage = "url(" + settings.avatar + ")";
                username.innerHTML = settings.user;
                messageNum.value = settings.mNum;
                
            })
        },
        getSettings = function()
        {
            var avFD = new FormData();
            avFD.append("user", settings.user);
            hf.ajax("POST", avFD, "phpSrc/getAvatar.php", function(res)
            {
                settings.avatar = res;
                hf.ajax("GET", null, "phpSrc/getSettings.php", function(res)
                {
                    // console.log(res);
                    res = JSON.parse(res);
                    settings.mNum = res["mPerPage"];
                    navigation.init();

                });
            });
        },
        changeAvatar = function()
        {
            var
            reader = new FileReader(),
            fd = new FormData(),
            avatarInput = hf.elCN("avatar-input")[0],
            avatarImg = hf.elCN("avatar")[0];

            avatarInput.click();

            avatarInput.onchange = function()
            {
            reader.onload = (function()
            {
            return function(res)
            {
                fd.append("avatar", res.target.result);
                settings.avatar = res.target.result;
                hf.ajax("POST", fd, "phpSrc/changeAvatar.php", function(r)
                {
                    r = JSON.parse(r);
                    if (r.code)
                        avatarImg.style.backgroundImage = "url(" + settings.avatar + ")";
                })
            }
            })();
            reader.readAsDataURL(avatarInput.files[0]);
            }
        },
        downloadMessages = function()
        {
            hf.ajax("GET", null, "phpSrc/downloadMessages.php", function(res)
            {
                var a = hf.cEL("a", {target: "_blank", href: 'data:text/plain;charset=utf-8,' + encodeURIComponent(res)});
                a.download = "Decrypted-Messages.txt";
                a.click();
            })
        };
        return {
            user: "",
            avatar: "",
            mNum: 10,
            init: function()
            {
                getTemplate();
            },
            getUserSettings: function()
            {
                getSettings();
            },
            click: function(ev)
            {
                var
                e = ev.target,
                settingsModule = hf.elCN("settings-container")[0],
                avatarContainer = hf.elCN("avatar-container")[0];

                if (hf.isInside(e, settingsModule))
                {
                    if (hf.isInside(e, avatarContainer))
                    {
                        changeAvatar();
                    }
                    else if (hf.cN(e, "settings-download-creds"))
                    {

                    }
                    else if (hf.cN(e, "settings-download-messages"))
                    {
                        downloadMessages();
                    }
                }
            }
        }
    })(),
    navigation = (function()
    {
        var
        buildNavigation = function(res)
        {
            var
            container = hf.cEL("div", {class: "navigation-container"}),
            avatar = hf.elCN("nav-avatar")[0];
            container.innerHTML = res;

            document.body.appendChild(container);
            avatar.style.backgroundImage = "url(" + settings.avatar + ")";
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
                    body.removeChild(body.children[i]);
            })();

            (function()
            {
                for (var i = 0, len = nav.children.length; i < len; i++)
                    nav.children[i].classList.remove("active");
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
                    else if (hf.isInside(e, hf.elCN("nav-avatar-container")[0]))
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
            var fd = new FormData();

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
                        hf.elCN("loginerror")[0].innerText = r.message;

                        document.body.innerHTML = "";
                        settings.user = un;
                        settings.getUserSettings();
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
                loginModule = hf.elCN("login-box")[0];

                if (hf.isInside(e, loginModule))
                {
                    var
                    un = loginModule.getElementsByTagName("input")[0],
                    pw = loginModule.getElementsByTagName("input")[1],
                    sub = loginModule.getElementsByTagName("button")[0];
                    if (e == un)
                    {

                    }
                    else if (e == pw)
                    {

                    }
                    else if (e == sub)
                    {
                        settings.getUserSettings();
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
            settings.click(ev);
        }
    };
})();

window.onclick = function(ev)
{
    APP.clicked(ev);
}