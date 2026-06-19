addEventListener('DOMContentLoaded', () => {

    const addEventButton = document.querySelector('#add-event')
    const positionSnapshot = new Map()
    let draggedRow = null

    getEvents()
    currentRows()
    capturePositions()

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
                    elImages[0].querySelector('.ia-email-event-image-image-id').value = data.image.id
                    elImages[1].remove()
                } else {
                    elImages[0].querySelector('.ia-email-event-image-preview').src = data.image.url
                    elImages[0].querySelector('.ia-email-event-image-image-id').value = data.image.id
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
                elImages[0].querySelector('.ia-email-event-image-image-id').value = ''
                elImages[1].remove()
            } else {
                elImages[0].querySelector('.ia-email-event-image-preview').src = ''
                elImages[0].querySelector('.ia-email-event-image-image-id').value = ''
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

        let rows = document.querySelectorAll('.ia-email-events-row')
        let headerImageButton = document.querySelector('.ia-email-select-image-header')
        initSelectHeaderImage(headerImageButton)


        for (let [i, row] of rows.entries()) {
            let selectImageButton = row.querySelector('.ia-email-select-image')
            let selectMinimizeButton = row.querySelector('.ia-email-minimize')
            let selectMaximizeButton = row.querySelector('.ia-email-maximize')
            let selectRemoveButton = row.querySelector('.ia-email-remove')
            let selectDropdown = row.querySelector('.ia-email-tec-dropdown')
            let selectMultiImage = row.querySelector('[name="ia-email-events[][event-two-imgs]"]')
            let selectDivider = row.querySelector('[name="ia-email-events[][event-divider]"]')
            let selectMute = row.querySelector('[name="ia-email-events[][event-mute]"]')
            let selectEventButtonAdd = row.querySelectorAll('.ia-email-button-add')
            let selectEventButtonRemove = row.querySelectorAll('.ia-email-button-remove')
            let selectMoveRowDown = row.querySelector('.ia-email-move-down')
            let selectMoveRowUp = row.querySelector('.ia-email-move-up')
            let isMinimized = row.querySelector('[name="ia-email-events[][event-minimized]"]')

            row.dataset.dirty = 'false'
            row.addEventListener('change', () => { row.dataset.dirty = 'true' })
            row.addEventListener('input', () => { row.dataset.dirty = 'true' })

            selectMinimizeButton.addEventListener('click', (e) => {
                e.preventDefault()
                selectMinimizeButton.parentNode.parentNode.parentNode.classList.add('ia-email-events-row-hide')
                isMinimized.value = "yes"
                row.dataset.dirty = 'true'
            })

            selectMaximizeButton.addEventListener('click', (e) => {
                e.preventDefault()
                selectMaximizeButton.parentNode.parentNode.parentNode.classList.remove('ia-email-events-row-hide')
                isMinimized.value = "no"
                row.dataset.dirty = 'true'
            })

            selectRemoveButton.addEventListener('click', (e) => {
                e.preventDefault()
                if (i > 0) {
                    //selectRemoveButton.parentNode.parentNode.parentNode.remove()
                    selectRemoveButton.parentNode.parentNode.parentNode.querySelector('.event-row-header').value = 'delete';
                    selectRemoveButton.parentNode.parentNode.parentNode.style.display = 'none';
                    row.dataset.dirty = 'true'
                }
            })


            selectMultiImage.addEventListener('click', () => {
                const parentEl = selectMultiImage.parentElement.parentElement.parentElement
                const imgWrap = parentEl.querySelector('.ia-email-event-image-wrapper')
                const imgWrapClone = imgWrap.cloneNode(true)
                if (selectMultiImage.checked) {
                    imgWrap.after(imgWrapClone)
                    imgWrapClone.querySelector('.ia-email-event-image-id').value = ''
                    imgWrapClone.querySelector('.ia-email-event-image-preview').src = ''
                    initSelectImage(imgWrapClone.querySelector('.ia-email-select-image'))
                } else {
                    imgWrap.nextElementSibling.remove()
                }
            })

            handleDivider(selectDivider)
            selectDivider.addEventListener('click', () => {
                handleDivider(selectDivider)
            })

            handleMute(selectMute)
            selectMute.addEventListener('click', () => {
                handleMute(selectMute)
            })

            selectEventButtonAdd.forEach((e) => {
                e.addEventListener('click', (f) => {
                    f.preventDefault()
                    createEventButtons(e)
                })
            })

            selectEventButtonRemove.forEach((e, i) => {
                e.addEventListener('click', (f) => {
                    f.preventDefault()
                    if (i > 0) {
                        //e.parentElement.parentElement.remove()
                        e.parentElement.parentElement.querySelector('.event-button-text').value = 'delete';
                        e.parentElement.parentElement.style.display = 'none';
                    }
                })
            })

            initSelectImage(selectImageButton)
            initDraggable(row)


            selectDropdown.addEventListener('change', (e) => {
                populateRow(selectDropdown, selectDropdown.value)
            })

            selectMoveRowDown.addEventListener('click', (e) => {
                e.preventDefault()
                moveRowDown(selectMoveRowDown)
            })


            selectMoveRowUp.addEventListener('click', (e) => {
                e.preventDefault()
                moveRowUp(selectMoveRowUp)
            })
        }
    }

    function createEvent() {
        const el = document.createElement('div')
        const emailEventsWrapper = document.querySelector('.ia-email-events-wrapper')
        el.classList.add('ia-email-events-row')
        el.innerHTML = document.querySelector('.ia-email-events-row').innerHTML
        emailEventsWrapper.append(el)
        el.dataset.dirty = 'true'
        initDraggable(el)
        el.querySelector('.ia-email-event-image-preview').src = ''
        el.querySelector('.ia-email-event-image-id').value = ''
        //CLB 1/25/25 - incremental saves
        el.querySelector('.ia-email-event-id').value = ''
        el.querySelector('.ia-email-event-image-image-id').value = ''
        //CLB 1/25/25 - incremental saves

        let imageButton = el.querySelector('.ia-email-select-image')
        let moveRowDownButton = el.querySelector('.ia-email-move-down')
        let moveRowUpButton = el.querySelector('.ia-email-move-up')
        let minimizeButton = el.querySelector('.ia-email-minimize')
        let maximizeButton = el.querySelector('.ia-email-maximize')
        let removeButton = el.querySelector('.ia-email-remove')
        let dropdown = el.querySelector('.ia-email-tec-dropdown')
        let featured = el.querySelector('[name="ia-email-events[][event-featured]"]')
        let multiImage = el.querySelector('[name="ia-email-events[][event-two-imgs]"]')
        let divider = el.querySelector('[name="ia-email-events[][event-divider]"]')
        let mute = el.querySelector('[name="ia-email-events[][event-mute]"]')
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
            el.dataset.dirty = 'true'
        })

        maximizeButton.addEventListener('click', (e) => {
            e.preventDefault()
            el.classList.remove('ia-email-events-row-hide')
            el.dataset.dirty = 'true'
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

        divider.addEventListener('click', () => {
            handleDivider(divider)
        })

        mute.addEventListener('click', () => {
            handleMute(mute)
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
                el.parentElement.getElementsByClassName("ia-email-event-image-preview")[0].src = attachment.url
                el.parentElement.getElementsByClassName("ia-email-event-image-image-id")[0].value = attachment.id
                wp.media.model.settings.post.id = wp_media_post_id
                const dirtyRow = el.closest('.ia-email-events-row')
                if (dirtyRow) dirtyRow.dataset.dirty = 'true'
            })
            file_frame.open()
        })
    }

    function initSelectHeaderImage(el) {
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

    function handleDivider(el) {
        const parentEl = el.parentElement.parentElement.parentElement.parentElement
        if (el.checked) {
            parentEl.querySelector('.ia-email-events-row-header-label').textContent = 'Divider: ' + parentEl.querySelector('.event-row-header').value;
            parentEl.querySelector('.ia-email-events-get-tec').style.display = 'none'
            parentEl.querySelector('.ia-email-tec-dropdown').value = 'none'
            parentEl.querySelector('[for="ia-email-event-image"]').style.display = 'none'
            parentEl.querySelector('.ia-email-event-image-wrapper').style.display = 'none'
            parentEl.querySelector('.ia-email-event-image-preview').src = ''
            parentEl.querySelector('.ia-email-event-button-wrapper').style.display = 'none'
            parentEl.querySelector('[name="ia-email-events[][event-button][text][]"]').value = ''
            parentEl.querySelector('[name="ia-email-events[][event-button][link][]"]').value = ''
        } else {
            parentEl.querySelector('.ia-email-events-get-tec').style.display = ''
            parentEl.querySelector('[for="ia-email-event-image"]').style.display = ''
            parentEl.querySelector('.ia-email-event-image-wrapper').style.display = ''
            parentEl.querySelector('.ia-email-event-button-wrapper').style.display = ''
        }
    }

    function handleMute(el) {
        const parentEl = el.parentElement.parentElement.parentElement.parentElement
        if (el.checked) {
            parentEl.style.opacity = '.6'
        } else {
            parentEl.style.opacity = '1'
        }
    }

    const eventsWrapper = document.querySelector('.ia-email-events-wrapper')
    eventsWrapper.addEventListener('dragover', (e) => {
        e.preventDefault()
        document.querySelectorAll('.ia-email-row-drag-above, .ia-email-row-drag-below').forEach(r => {
            r.classList.remove('ia-email-row-drag-above', 'ia-email-row-drag-below')
        })
        const targetRow = e.target.closest('.ia-email-events-row')
        if (targetRow && targetRow !== draggedRow) {
            const rect = targetRow.getBoundingClientRect()
            if (e.clientY < rect.top + rect.height / 2) {
                targetRow.classList.add('ia-email-row-drag-above')
            } else {
                targetRow.classList.add('ia-email-row-drag-below')
            }
        }
    })

    eventsWrapper.addEventListener('dragleave', (e) => {
        if (!eventsWrapper.contains(e.relatedTarget)) {
            document.querySelectorAll('.ia-email-row-drag-above, .ia-email-row-drag-below').forEach(r => {
                r.classList.remove('ia-email-row-drag-above', 'ia-email-row-drag-below')
            })
        }
    })

    eventsWrapper.addEventListener('drop', (e) => {
        e.preventDefault()
        const targetRow = e.target.closest('.ia-email-events-row')
        if (targetRow && draggedRow && targetRow !== draggedRow) {
            const rect = targetRow.getBoundingClientRect()
            if (e.clientY < rect.top + rect.height / 2) {
                targetRow.before(draggedRow)
            } else {
                targetRow.after(draggedRow)
            }
        }
        document.querySelectorAll('.ia-email-row-drag-above, .ia-email-row-drag-below').forEach(r => {
            r.classList.remove('ia-email-row-drag-above', 'ia-email-row-drag-below')
        })
    })

    function initDraggable(row) {
        row.setAttribute('draggable', 'true')
        row.addEventListener('dragstart', (e) => {
            draggedRow = row
            e.dataTransfer.effectAllowed = 'move'
            e.dataTransfer.setData('text/plain', '')
            const header = row.querySelector('.ia-email-events-row-header')
            if (header) {
                e.dataTransfer.setDragImage(header, header.offsetWidth / 2, header.offsetHeight / 2)
            }
            setTimeout(() => row.classList.add('ia-email-row-dragging'), 0)
        })
        row.addEventListener('dragend', () => {
            row.classList.remove('ia-email-row-dragging')
            document.querySelectorAll('.ia-email-row-drag-above, .ia-email-row-drag-below').forEach(r => {
                r.classList.remove('ia-email-row-drag-above', 'ia-email-row-drag-below')
            })
            draggedRow = null
        })
    }

    function capturePositions() {
        document.querySelectorAll('.ia-email-events-row').forEach((row, index) => {
            const eventIdInput = row.querySelector('.ia-email-event-id')
            if (eventIdInput && eventIdInput.value) {
                positionSnapshot.set(eventIdInput.value, index)
            }
        })
    }

    if (typeof tinyMCE !== 'undefined') {
        tinyMCE.on('AddEditor', (e) => {
            const textarea = document.getElementById(e.editor.id)
            if (textarea) {
                const row = textarea.closest('.ia-email-events-row')
                if (row) {
                    e.editor.on('Change', () => { row.dataset.dirty = 'true' })
                }
            }
        })
    }

    document.querySelector('form').addEventListener('submit', () => {
        document.querySelectorAll('.ia-email-events-row').forEach((row, index) => {
            row.querySelectorAll('[name="ia-email-events[][event-unchanged]"]').forEach(m => m.remove())
            const eventIdInput = row.querySelector('.ia-email-event-id')
            const eventId = eventIdInput ? eventIdInput.value : ''
            const isDirty = row.dataset.dirty === 'true'
            const isNew = !eventId
            const originalPos = positionSnapshot.get(eventId)
            const positionChanged = originalPos !== undefined && originalPos !== index
            if (!isDirty && !isNew && !positionChanged) {
                const marker = document.createElement('input')
                marker.type = 'hidden'
                marker.name = 'ia-email-events[][event-unchanged]'
                marker.value = 'yes'
                row.appendChild(marker)
            }
        })
    })

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