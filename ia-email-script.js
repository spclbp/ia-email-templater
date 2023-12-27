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

            initDropdowns = document.querySelectorAll('.ia-email-tec-dropdown')

            for (let dropdown of initDropdowns) {
                if (dropdown.options.length <= 2) {
                    for (let event of filteredData) {
                        let option = document.createElement('option')
                        option.textContent = event.title.toString().replace(/(<([^>]+)>)/ig, '').replace('#038;', '')
                        option.value = event.id
                        dropdown.append(option)
                    }
                }
            }
        })
    }

    function populateRow(el, id) {
        let elParent = el.parentElement.parentElement
        let rowLabel = el.parentElement.parentElement.previousElementSibling.querySelector('.ia-email-events-row-header-label')
        let elHeader = elParent.querySelector('[name="ia-email-events[][event-header]"]')
        let elImages = elParent.querySelectorAll('.ia-email-event-image-wrapper')
        let elText = elParent.querySelector('[name="ia-email-events[][event-text]"]')
        let elButtonText = elParent.querySelector('[name="ia-email-events[][event-button][text][]"]')
        let elLink = elParent.querySelector('[name="ia-email-events[][event-button][link][]"]')
        let elTwoImages = elParent.querySelector('[name="ia-email-events[][event-two-imgs]"]')
        let elButtonRows = elParent.querySelectorAll('.ia-email-event-button-wrapper')
        if (id != 'none') {
            fetch(`https://www.indyambassadors.org/wp-json/tribe/events/v1/events/${id}`).then(res => res.json()).then(data => {
                rowLabel.textContent = data.title
                elHeader.value = data.title
                if (elImages.length > 1) {
                    elImages[0].querySelector('.ia-email-event-image-preview').src = data.image.url
                    elImages[0].querySelector('.ia-email-event-image-id').value = data.image.id
                    elImages[1].remove()
                } else {
                    elImages[0].querySelector('.ia-email-event-image-preview').src = data.image.url
                    elImages[0].querySelector('.ia-email-event-image-id').value = data.image.id
                }
                elTwoImages.checked = false
                elText.value = data.description
                elButtonText.value = 'Volunteer'
                elLink.value = data.url
            })
        } else {
            rowLabel.textContent = ''
            elHeader.value = ''
            if (elImages.length > 1) {
                elImages[0].querySelector('.ia-email-event-image-preview').src = ''
                elImages[0].querySelector('.ia-email-event-image-id').value = ''
                elImages[1].remove()
            } else {
                elImages[0].querySelector('.ia-email-event-image-preview').src = ''
                elImages[0].querySelector('.ia-email-event-image-id').value = ''
            }
            elTwoImages.checked = false
            elText.value = ''
            elButtonText.value = ''
            elLink.value = ''
            for (let i = elButtonRows.length - 1; i > 0; i--) {
                elButtonRows[i].remove()
            }
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
        let selectMoveRowDown = document.querySelectorAll('.ia-email-move-down')
        let selectMoveRowUp = document.querySelectorAll('.ia-email-move-up')

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

        selectMoveRowDown.forEach((e) => {
            e.addEventListener('click', (f) => {
                f.preventDefault()

                moveRowDown(e)
            })
        })

        selectMoveRowUp.forEach((e) => {
            e.addEventListener('click', (f) => {
                f.preventDefault()
                moveRowUp(e)
            })
        })
    }

    function createEvent() {
        const el = document.createElement('div')
        const emailEventsWrapper = document.querySelector('.ia-email-events-wrapper')
        el.classList.add('ia-email-events-row')
        el.innerHTML = document.querySelector('.ia-email-events-row').innerHTML
        emailEventsWrapper.append(el)
        el.querySelector('.ia-email-event-image-preview').src = ''
        el.querySelector('.ia-email-event-image-id').value = ''

        let imageButton = el.querySelector('.ia-email-select-image')
        let moveRowDownButton = el.querySelector('.ia-email-move-down')
        let moveRowUpButton = el.querySelector('.ia-email-move-up')
        let minimizeButton = el.querySelector('.ia-email-minimize')
        let maximizeButton = el.querySelector('.ia-email-maximize')
        let removeButton = el.querySelector('.ia-email-remove')
        let dropdown = el.querySelector('.ia-email-tec-dropdown')
        let featured = el.querySelector('[name="ia-email-events[][event-featured]"]')
        let multiImage = el.querySelector('[name="ia-email-events[][event-two-imgs]"]')
        let eventButtonAdd = el.querySelector('.ia-email-button-add')
        let eventButtonRemove = el.querySelector('.ia-email-button-remove')
        dropdown.value = 'none'
        featured.checked = false
        multiImage.checked = false

        if (el.querySelectorAll('.ia-email-event-image-wrapper').length > 1) {
            el.querySelectorAll('.ia-email-event-image-wrapper')[1].remove()
        }

        if (el.querySelectorAll('.ia-email-event-button-wrapper').length > 1) {
            el.querySelectorAll('.ia-email-event-button-wrapper')[1].remove()
        }

        populateRow(dropdown, dropdown.value)
        initSelectImage(imageButton)

        moveRowDownButton.addEventListener('click', (e) => {
            e.preventDefault()
            moveRowDown(moveRowDownButton)
        })

        moveRowUpButton.addEventListener('click', (e) => {
            e.preventDefault()
            moveRowUp(moveRowUpButton)
        })

        minimizeButton.addEventListener('click', (e) => {
            e.preventDefault()
            el.classList.add('ia-email-events-row-hide')
        })

        maximizeButton.addEventListener('click', (e) => {
            e.preventDefault()
            el.classList.remove('ia-email-events-row-hide')
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
        newBtnRow.classList.add('ia-email-event-button-wrapper')
        newBtnRow.innerHTML = document.querySelector('.ia-email-event-button-wrapper').innerHTML
        newBtnRow.querySelectorAll('input').forEach((e) => {
            e.value = ''
        })
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

    function moveRowDown(el) {
        let eventRows = [...document.querySelectorAll('.ia-email-events-row')]
        let moveDownBtns = [...document.querySelectorAll('.ia-email-move-down')]
        let rowIndex = moveDownBtns.indexOf(el)
        if (rowIndex < eventRows.length - 1) {
            eventRows[rowIndex + 1].after(eventRows[rowIndex])
        }
    }

    function moveRowUp(el) {
        let eventRows = [...document.querySelectorAll('.ia-email-events-row')]
        let moveDownBtns = [...document.querySelectorAll('.ia-email-move-up')]
        let rowIndex = moveDownBtns.indexOf(el)
        if (rowIndex > 0) {
            eventRows[rowIndex - 1].before(eventRows[rowIndex])
        }
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
})