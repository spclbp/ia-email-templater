addEventListener('DOMContentLoaded', () => {

    const addEventButton = document.querySelector('#add-event')

    getEvents()
    currentRows()

    addEventButton.addEventListener('click', (e) => {
        e.preventDefault()
        createEvent()
    })

    function getEvents() {
        fetch('https://www.indyambassadors.org/wp-json/tribe/events/v1/events/?page=1&per_page=50&start_date=today').then(res => res.json()).then(data => {

            let filteredData = data.events.filter((value, index, self) => {
                return self.findIndex(event => event.title === value.title) === index;
              })

            console.log(filteredData)

            initDropdowns = document.querySelectorAll('.ia-email-tec-dropdown')

            for (let dropdown of initDropdowns) {
                for (let event of filteredData) {
                    let option = document.createElement('option')
                    option.textContent = event.title.toString().replace(/(<([^>]+)>)/ig, '').replace('#038;', '')
                    option.value = event.id
                    dropdown.append(option)
                }
            }
        })
    }

    function populateRow(el, id) {
        let elParent = el.parentElement.parentElement
        let elHeader = elParent.querySelector('[name="ia-email-events[][event-header]"]')
        let elImage = elParent.querySelector('.ia-email-event-image-wrapper')
        let elText = elParent.querySelector('[name="ia-email-events[][event-text]"]')
        let elButtonText = elParent.querySelector('[name="ia-email-events[][event-button][text][]"]')
        let elLink = elParent.querySelector('[name="ia-email-events[][event-button][link][]"]')
        if (id != 'none') {
            fetch(`https://www.indyambassadors.org/wp-json/tribe/events/v1/events/${id}`).then(res => res.json()).then(data => {
                elHeader.value = data.title
                elImage.querySelector('.ia-email-event-image-preview').src = data.image.url
                elImage.querySelector('.ia-email-event-image-id').value = data.image.id
                elText.value = data.description
                elButtonText.value = 'Volunteer'
                elLink.value = data.url
            })
        } else {
            elHeader.value = ''
            elImage.querySelector('.ia-email-event-image-preview').src = ''
            elImage.querySelector('.ia-email-event-image-id').value = ''
            elText.value = ''
            elButtonText.value = ''
            elLink.value = ''
        }
    }

    function currentRows() {

        let selectImageButtons = document.querySelectorAll('.ia-email-select-image')
        let selectMinimizeButtons = document.querySelectorAll('.ia-email-minimize')
        let selectMaximizeButtons = document.querySelectorAll('.ia-email-maximize')
        let selectRemoveButtons = document.querySelectorAll('.ia-email-remove')
        let selectDropdowns = document.querySelectorAll('.ia-email-tec-dropdown')
        let selectMultiImage = document.querySelectorAll('[name="ia-email-events[][event-two-imgs]"]')
        let selectEventButtonAdd = document.querySelectorAll('.ia-email-button-add')
        let selectEventButtonRemove = document.querySelectorAll('.ia-email-button-remove')

        for (let minButton of selectMinimizeButtons) {
            minButton.addEventListener('click', (e) => {
                e.preventDefault()
                minButton.parentNode.parentNode.parentNode.classList.add('ia-email-events-row-hide')
            })
        }

        for (let maxButton of selectMaximizeButtons) {
            maxButton.addEventListener('click', (e) => {
                e.preventDefault()
                maxButton.parentNode.parentNode.parentNode.classList.remove('ia-email-events-row-hide')
            })
        }

        for (let [i, removeButton] of selectRemoveButtons.entries()) {
            removeButton.addEventListener('click', (e) => {
                e.preventDefault()
                if (i > 0) {
                    removeButton.parentNode.parentNode.parentNode.remove()
                }
            })
        }

        for (let multiImage of selectMultiImage) {
            multiImage.addEventListener('click', () => {
                const parentEl = multiImage.parentElement.parentElement.parentElement
                const imgWrap = parentEl.querySelector('.ia-email-event-image-wrapper')
                const imgWrapClone = imgWrap.cloneNode(true)
                if (multiImage.checked) {
                    imgWrap.after(imgWrapClone)
                    initSelectImage(imgWrapClone.querySelector('.ia-email-select-image'))
                } else {
                    imgWrap.nextElementSibling.remove()
                }
            })
        }

        selectEventButtonAdd.forEach((e) => {
            e.addEventListener('click', (f) => {
                f.preventDefault()
                createEventButtons(e)
            })
        })

        for (let [i, eventButtonRemove] of selectEventButtonRemove.entries()) {
            eventButtonRemove.addEventListener('click', (e) => {
                e.preventDefault()
                if (i > 0) {
                    eventButtonRemove.parentElement.parentElement.remove()
                }
            })
        }

        selectImageButtons.forEach((e) => {
            initSelectImage(e)
        })

        selectDropdowns.forEach((e) => {
            e.addEventListener('change', () => {
                populateRow(e, e.value)
            })
        })
    }

    function createEvent() {
        const el = document.createElement('div')
        const emailEventsWrapper = document.querySelector('.ia-email-events-wrapper')
        el.innerHTML = newEventTemplate
        el.querySelector('.ia-email-event-image-preview').src = ''
        el.querySelector('.ia-email-event-image-id').value = ''
        emailEventsWrapper.append(el)

        let rowContent = el.querySelector('.ia-email-events-row')
        let imageButton = el.querySelector('.ia-email-select-image')
        let minimizeButton = el.querySelector('.ia-email-minimize')
        let maximizeButton = el.querySelector('.ia-email-maximize')
        let removeButton = el.querySelector('.ia-email-remove')
        let dropdown = el.querySelector('.ia-email-tec-dropdown')
        let featured = el.querySelector('[name="ia-email-events[][event-featured]"]')
        let multiImage = el.querySelector('[name="ia-email-events[][event-two-imgs]"]')
        let eventButtonAdd = el.querySelector('.ia-email-button-add')
        let eventButtonRemove = el.querySelector('.ia-email-button-remove')
        dropdown.value = 'none'
        populateRow(dropdown, dropdown.value)
        featured.checked = false
        multiImage.checked = false
        initSelectImage(imageButton)


        minimizeButton.addEventListener('click', (e) => {
            e.preventDefault()
            rowContent.classList.add('ia-email-events-row-hide')
        })

        maximizeButton.addEventListener('click', (e) => {
            e.preventDefault()
            rowContent.classList.remove('ia-email-events-row-hide')
        })

        removeButton.addEventListener('click', (e) => {
            e.preventDefault()
            el.remove()
        })

        multiImage.addEventListener('click', () => {
            const imgWrap = el.querySelector('.ia-email-event-image-wrapper')
            const imgWrapClone = imgWrap.cloneNode(true)
            if (multiImage.checked) {
                imgWrap.after(imgWrapClone)
                initSelectImage(imgWrapClone.querySelector('.ia-email-select-image'))
            } else {
                imgWrap.nextElementSibling.remove()
            }
        })

        eventButtonAdd.addEventListener('click', (e) => {
            e.preventDefault()
            createEventButtons(eventButtonAdd)
        })

        eventButtonRemove.addEventListener('click', (e) => {
            e.preventDefault()
        })

        dropdown.addEventListener('change', () => {
            populateRow(dropdown, dropdown.value)
        })

        getEvents()
    }

    function initSelectImage(el) {
        let file_frame
        let wp_media_post_id = wp.media.model.settings.post.id
        let set_to_post_id = el.previousElementSibling.value
        el.addEventListener('click', (e) => {
            e.preventDefault()

            if (file_frame) {
                file_frame.uploader.uploader.param('post_id', set_to_post_id)
                file_frame.open()
                return
            } else {
                wp.media.model.settings.post.id = set_to_post_id
            }

            file_frame = wp.media.frames.file_frame = wp.media({
                title: 'Select a image to upload',
                button: {
                    text: 'Use this image',
                },
                multiple: false
            })

            file_frame.on('select', function () {
                attachment = file_frame.state().get('selection').first().toJSON()
                el.previousElementSibling.previousElementSibling.src = attachment.url
                el.previousElementSibling.value = attachment.id
                wp.media.model.settings.post.id = wp_media_post_id
            })
            file_frame.open()
        })
    }

    function createEventButtons(el) {
        let elParent = el.parentElement.parentElement
        let newBtnRow = document.createElement('div')
        newBtnRow.innerHTML = eventButtonTemplate
        elParent.after(newBtnRow)
        let newAddBtn = newBtnRow.querySelector('.ia-email-button-add')
        let newRemBtn = newBtnRow.querySelector('.ia-email-button-remove')
        newAddBtn.addEventListener('click', (e) => {
            e.preventDefault()
            createEventButtons(newAddBtn)
        })
        newRemBtn.addEventListener('click', (e) => {
            e.preventDefault()
            newBtnRow.remove()
        })
    }

    document.querySelector('#copy-code').addEventListener('click', (e) => {
        e.preventDefault()
        navigator.clipboard.writeText(document.querySelector('#the-preview').innerHTML)
        document.querySelector('#copy-code').classList.add('green-pulse')
    })

    document.querySelector('#the-code').textContent = document.querySelector('#the-preview').innerHTML

    document.querySelector('#toggle-code').addEventListener('click', (e) => {
        e.preventDefault()
        let theCodeEl = document.querySelector('#the-code')
        if (theCodeEl.style.display == '') {
            theCodeEl.style.display = 'inline'
        } else {
            theCodeEl.style.display = ''
        }
    })

    const newEventTemplate = `
                    <div class="ia-email-events-row">
                    <div class="ia-email-events-row-header">
                        <h3 class="ia-email-events-row-header-text">Event Row</h3>
                        <div class="ia-email-events-row-buttons">
                            <button class="ia-email-button-small ia-email-minimize">_</button>
                            <button class="ia-email-button-small ia-email-maximize">+</button>
                            <button class="ia-email-button-small ia-email-remove">x</button>
                        </div>
                    </div>
                    <div class="ia-email-events-row-content">
                        <div class="ia-email-events-get-tec">
                            <label for="ia-email-tec-dropdown">TEC Event</label>
                            <select class="ia-email-tec-dropdown" name="ia-email-events[][tec-dropdown]">
                                <option value="none">None</option>
                                    <option value="none" selected></option>
                            </select>
                        </div>
                        <div class="ia-email-events-row-props">
                            <div class="ia-email-events-featured">
                                <label for="ia-email-event-featured">Featured</label>
                                <input type="checkbox" name="ia-email-events[][event-featured]"></input>
                            </div>
                            <div class="ia-email-events-two-imgs">
                                <label for="ia-email-event-two-imgs">Two Images</label>
                                <input type="checkbox" name="ia-email-events[][event-two-imgs]"></input>
                            </div>
                        </div>
                        <label for="ia-email-event-header">Event Row Header</label>
                        <input type="text" name="ia-email-events[][event-header]" value=""></input>
                        <label for="ia-email-event-image">Event Row Image</label>
                            <div class="ia-email-event-image-wrapper">
                                <img src="" alt="Event Image Preview" class="ia-email-event-image-preview">
                                <input type="hidden" name="ia-email-events[][event-image-id][]" class="ia-email-event-image-id" value="">
                                <input type="button" value="Choose Image" class="ia-email-button ia-email-select-image">
                            </div>
                        <label for="ia-email-event-text">Event Row Text</label>
                        <textarea name="ia-email-events[][event-text]" rows="5"></textarea>
                        <div class="ia-email-event-button-wrapper">
                            <div class="ia-email-event-button-inputs">
                                <label for="ia-email-event-button-text">Event Row Button Text</label>
                                <input type="text" name="ia-email-events[][event-button][text][]" value=""></input>
                                <label for="ia-email-event-link">Event Row Button Link</label>
                                <input type="text" name="ia-email-events[][event-button][link][]" value=""></input>
                            </div>
                            <div class="ia-email-event-button-controls">
                                <button class="ia-email-button-small ia-email-button-add">+</button>
                                <button class="ia-email-button-small ia-email-button-remove">-</button>
                            </div>
                        </div>
                    </div>
                </div>
                `
    const eventButtonTemplate = `
                        <div class="ia-email-event-button-wrapper">
                            <div class="ia-email-event-button-inputs">
                                <label for="ia-email-event-button-text">Event Row Button Text</label>
                                <input type="text" name="ia-email-events[][event-button][text][]" value=""></input>
                                <label for="ia-email-event-link">Event Row Button Link</label>
                                <input type="text" name="ia-email-events[][event-button][link][]" value=""></input>
                            </div>
                            <div class="ia-email-event-button-controls">
                                <button class="ia-email-button-small ia-email-button-add">+</button>
                                <button class="ia-email-button-small ia-email-button-remove">-</button>
                            </div>
                        </div>
                        `
})