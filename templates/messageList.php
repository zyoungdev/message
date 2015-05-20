<div class="message-list-controls-container">
    <!-- <button type="" class="create-message-button">New Message</button> -->
        <button type="" class="refresh-messages-button">Refresh</button>
        <input type="text" class="find-message-input" placeholder="Fingerprint i.e jc74b30c">
        <button class="find-message-button">Find</button>
    <button type="" class="delete-multiple-messages-button">Delete</button>
</div>

<div class="message-list-heading">
    <div class="check-container">
        <input type="checkbox" class="message-list-heading-checkbox">
    </div>
    <div class="message-list-size">Size</div>
    <div class="message-list-username">Sender</div>
    <div class="message-list-fingerprint">Message</div>
    <div class="message-list-timestamp">Timestamp</div>
</div>

<!-- We will put the message in this list -->
<div class="message-list"></div>

<!-- Page numbers -->
<div class="message-list-pagenum-container">
    <input type="number" min="0" class="message-list-pagenum-input">
    <div class="message-list-pagenum-total">of 10</div>
</div>

<!-- Pagination -->
<div class="message-list-page-container">
    <div class="page-controls">
        <button class="next">&gt; &gt;</button><button class="prev">&lt; &lt;</button>
    </div>
</div>