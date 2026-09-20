addEventListener('DOMContentLoaded', () => {

    const addEventButton = document.querySelector('#add-event')
    const saveButton = document.querySelector('#ia-email-save')
    const form = document.querySelector('form')
    const positionSnapshot = new Map()
    let draggedRow = null
    let hasUnsavedChanges = false
    let bypassUnloadWarning = false

    const promotionalImageButton = document.querySelector('.ia-email-select-promotional-image')
    if (promotionalImageButton) initPromotionalImagePicker(promotionalImageButton)
    initEventEmojiPicker()

    if (!addEventButton || !form) return

    const markUnsavedChanges = () => {
        hasUnsavedChanges = true
    }

    const toPlainText = (value) => {
        if (typeof value !== 'string' || value === '') return ''
        const doc = new DOMParser().parseFromString(value, 'text/html')
        return (doc.body.textContent || '').replace(/\s+/g, ' ').trim()
    }

    const getEmailHtml = () => {
        const preview = document.querySelector('#the-preview').cloneNode(true)
        preview.querySelectorAll('img.emoji, img.wp-smiley').forEach((image) => {
            image.style.width = '1em'
            image.style.height = '1em'
            image.style.maxWidth = 'none'
            image.style.display = 'inline'
            image.style.verticalAlign = '-.1em'
        })
        return preview.innerHTML
    }

    const getSubstackHtml = () => {
        const root = document.querySelector('#the-preview').cloneNode(true)
        const isBlockTag = (el) => /^(P|H[1-6]|UL|OL|BLOCKQUOTE|HR)$/.test(el.tagName)
        const isImageBlock = (el) => el.tagName === 'IMG' || (el.tagName === 'A' && el.children.length === 1 && el.firstElementChild.tagName === 'IMG' && !el.textContent.trim())

        root.querySelectorAll('img.emoji, img.wp-smiley').forEach((image) => {
            image.replaceWith(document.createTextNode(image.getAttribute('alt') || ''))
        })
        root.querySelectorAll('span[style*="font-size: 0px"]').forEach((el) => el.remove())

        root.querySelectorAll('a[style*="border: 4px solid"]').forEach((a) => {
            a.dataset.button = '1'
            a.textContent = a.textContent.trim()
        })

        root.querySelectorAll('tr[style*="Gainsboro"] span[style*="36px"]').forEach((span) => {
            const h = document.createElement('h2')
            h.textContent = span.textContent.trim()
            span.closest('tr').replaceWith(h)
        })
        root.querySelectorAll('h3').forEach((h3) => {
            const h = document.createElement('h3')
            h.innerHTML = h3.innerHTML
            h3.replaceWith(h)
        })

        root.querySelectorAll('table table').forEach((table) => {
            const ul = document.createElement('ul')
            table.querySelectorAll('a').forEach((a) => {
                const li = document.createElement('li')
                li.appendChild(a.cloneNode(true))
                ul.appendChild(li)
            })
            table.replaceWith(ul)
        })

        const unwrapTags = 'table, tbody, thead, tfoot, tr, td, th, div, span, nobr, font, center'
        let wrapper
        while ((wrapper = root.querySelector(unwrapTags))) wrapper.replaceWith(...wrapper.childNodes)

        const out = document.createElement('div')
        let run = null
        const flushRun = () => {
            if (run && run.textContent.trim() !== '') out.appendChild(run)
            run = null
        }
        const addToRun = (node) => {
            if (!run) run = document.createElement('p')
            run.appendChild(node)
        }

        Array.from(root.childNodes).forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE && node.tagName !== 'BR' && (isBlockTag(node) || isImageBlock(node))) {
                flushRun()
                if (isImageBlock(node)) {
                    const p = document.createElement('p')
                    p.appendChild(node)
                    out.appendChild(p)
                } else {
                    out.appendChild(node)
                }
            } else if (node.nodeType === Node.ELEMENT_NODE && node.dataset && node.dataset.button) {
                flushRun()
                const last = out.lastElementChild
                if (last && last.dataset && last.dataset.buttons) {
                    last.appendChild(document.createTextNode(' | '))
                    last.appendChild(node)
                } else {
                    const p = document.createElement('p')
                    p.dataset.buttons = '1'
                    p.appendChild(node)
                    out.appendChild(p)
                }
            } else if (node.nodeType === Node.COMMENT_NODE) {
                return
            } else if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() === '' && !run) {
                return
            } else {
                addToRun(node)
            }
        })
        flushRun()

        out.querySelectorAll('p').forEach((p) => {
            p.querySelectorAll(':scope > p').forEach((inner) => inner.replaceWith(...inner.childNodes))
        })
        out.querySelectorAll('*').forEach((el) => {
            const keep = { A: ['href'], IMG: ['src', 'alt'] }[el.tagName] || []
            Array.from(el.attributes).forEach((attr) => {
                if (!keep.includes(attr.name)) el.removeAttribute(attr.name)
            })
        })
        out.querySelectorAll('p:empty').forEach((p) => p.remove())
        return out.innerHTML
    }

    const getSubstackDocument = () => {
        const doc = new DOMParser().parseFromString(getSubstackHtml(), 'text/html')
        const blockTags = new Set(['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'UL', 'OL', 'BLOCKQUOTE', 'IMG'])

        const getMarks = (element, inheritedMarks) => {
            const marks = inheritedMarks.map(mark => ({ type: mark.type, attrs: mark.attrs ? { ...mark.attrs } : undefined }))
            let current = element
            while (current && current.nodeType === Node.ELEMENT_NODE) {
                if (current.tagName === 'STRONG' || current.tagName === 'B') {
                    if (!marks.some(mark => mark.type === 'bold')) marks.push({ type: 'bold' })
                }
                if (current.tagName === 'EM' || current.tagName === 'I') {
                    if (!marks.some(mark => mark.type === 'italic')) marks.push({ type: 'italic' })
                }
                if (current.tagName === 'A' && current.getAttribute('href')) {
                    marks.push({
                        type: 'link',
                        attrs: {
                            href: current.getAttribute('href'),
                            target: current.getAttribute('target') || null,
                            rel: current.getAttribute('rel') || null,
                            class: current.getAttribute('class') || null
                        }
                    })
                }
                current = current.parentElement
            }
            return marks
        }

        const inlineContent = (parent, inheritedMarks = []) => {
            const content = []
            Array.from(parent.childNodes).forEach((child) => {
                if (child.nodeType === Node.TEXT_NODE) {
                    const text = child.textContent.replace(/\s+/g, ' ')
                    if (text.trim()) {
                        const marks = getMarks(child.parentElement, inheritedMarks)
                        content.push({
                            type: 'text',
                            text,
                            ...(marks.length
                                ? { marks }
                                : {})
                        })
                    } else if (content.length && child.nextSibling && child.nextSibling.nodeType === Node.ELEMENT_NODE && !blockTags.has(child.nextSibling.tagName)) {
                        content.push({ type: 'text', text: ' ' })
                    }
                    return
                }
                if (child.nodeType !== Node.ELEMENT_NODE) return
                if (child.tagName === 'BR') {
                    content.push({ type: 'hard_break' })
                    return
                }
                if (child.tagName === 'IMG') {
                    content.push(imageNode(child))
                    return
                }
                content.push(...inlineContent(child, getMarks(child, inheritedMarks)))
            })
            return content
        }

        const imageNode = (image) => ({
            type: 'image',
            attrs: {
                src: image.getAttribute('src') || '',
                alt: image.getAttribute('alt') || null
            }
        })

        const paragraphNode = (element) => {
            const content = inlineContent(element)
            return content.length ? { type: 'paragraph', content } : null
        }

        const listItemNode = (element) => {
            const content = []
            let inlineNodes = []
            const flushInlineNodes = () => {
                if (inlineNodes.length) content.push({ type: 'paragraph', content: inlineNodes })
                inlineNodes = []
            }

            Array.from(element.childNodes).forEach((child) => {
                if (child.nodeType === Node.ELEMENT_NODE && (child.tagName === 'UL' || child.tagName === 'OL')) {
                    flushInlineNodes()
                    const nestedList = listNode(child)
                    if (nestedList) content.push(nestedList)
                } else if (child.nodeType === Node.ELEMENT_NODE && blockTags.has(child.tagName)) {
                    flushInlineNodes()
                    const block = blockNode(child)
                    if (block) content.push(block)
                } else {
                    inlineNodes.push(...(child.nodeType === Node.TEXT_NODE
                        ? inlineContent({ childNodes: [child] })
                        : inlineContent(child)))
                }
            })
            flushInlineNodes()
            return content.length ? { type: 'list_item', content } : null
        }

        const listNode = (element) => {
            const content = Array.from(element.children)
                .filter(child => child.tagName === 'LI')
                .map(listItemNode)
                .filter(Boolean)
            if (!content.length) return null
            return {
                type: element.tagName === 'OL' ? 'ordered_list' : 'bullet_list',
                ...(element.tagName === 'OL' ? { attrs: { order: 1 } } : {}),
                content
            }
        }

        const blockNode = (element) => {
            if (element.tagName === 'IMG') return imageNode(element)
            if (element.tagName === 'UL' || element.tagName === 'OL') return listNode(element)
            if (/^H[1-6]$/.test(element.tagName)) {
                const content = inlineContent(element)
                return content.length ? {
                    type: 'heading',
                    attrs: { level: Number(element.tagName.substring(1)) },
                    content
                } : null
            }
            if (element.tagName === 'BLOCKQUOTE') {
                const content = blockContent(element)
                return content.length ? { type: 'blockquote', content } : null
            }
            return paragraphNode(element)
        }

        const blockContent = (parent) => {
            const content = []
            let inlineNodes = []
            const flushInlineNodes = () => {
                if (inlineNodes.length) content.push({ type: 'paragraph', content: inlineNodes })
                inlineNodes = []
            }

            Array.from(parent.childNodes).forEach((child) => {
                if (child.nodeType === Node.ELEMENT_NODE && blockTags.has(child.tagName)) {
                    flushInlineNodes()
                    const block = blockNode(child)
                    if (block) content.push(block)
                } else if (child.nodeType === Node.TEXT_NODE && child.textContent.trim() === '') {
                    return
                } else {
                    inlineNodes.push(...(child.nodeType === Node.TEXT_NODE
                        ? inlineContent({ childNodes: [child] })
                        : inlineContent(child)))
                }
            })
            flushInlineNodes()
            return content
        }

        return { type: 'doc', content: blockContent(doc.body) }
    }

    getEvents()
    currentRows()
    capturePositions()

    addEventButton.addEventListener('click', (e) => {
        e.preventDefault()
        createEvent()
    })

    form.addEventListener('input', markUnsavedChanges)
    form.addEventListener('change', markUnsavedChanges)

    if (saveButton) {
        saveButton.addEventListener('click', () => {
            bypassUnloadWarning = true
        })
    }

    window.addEventListener('beforeunload', (e) => {
        if (!hasUnsavedChanges || bypassUnloadWarning) return
        e.preventDefault()
        e.returnValue = ''
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
        let elMute = elParent.querySelector('[name="ia-email-events[][event-mute]"]')
        let elButtonRows = elParent.querySelectorAll('.ia-email-event-button-wrapper')
        if (id != 'none') {
            fetch(`https://www.indyambassadors.org/wp-json/tribe/events/v1/events/${id}`).then(res => res.json()).then(async data => {
                let promotionalImage = data.ia_email_promotional_image
                try {
                    const postResponse = await fetch(`https://www.indyambassadors.org/wp-json/wp/v2/tribe_events/${id}`)
                    if (postResponse.ok) {
                        const postData = await postResponse.json()
                        promotionalImage = postData.ia_email_promotional_image || promotionalImage
                        data.ia_email_event_emoji = postData.ia_email_event_emoji || data.ia_email_event_emoji
                    }
                } catch (error) {
                }

                const getPlainTextValue = (value) => {
                    if (typeof value === 'string') return value
                    if (value && typeof value.rendered === 'string') return value.rendered
                    return ''
                }

                const getRenderedValue = (value) => {
                    if (typeof value === 'string') return value
                    if (value && typeof value.rendered === 'string') return value.rendered
                    return ''
                }

                const getMeaningfulText = (html) => {
                    if (!html) return ''
                    const doc = new DOMParser().parseFromString(html, 'text/html')
                    return (doc.body.textContent || '').replace(/\s+/g, ' ').trim()
                }

                const cleanseDescription = (html) => {
                    if (!html) return ''
                    const doc = new DOMParser().parseFromString(html, 'text/html')
                    doc.querySelectorAll('img,script,style').forEach(node => node.remove())
                    const paragraphs = Array.from(doc.querySelectorAll('p'))
                        .map(p => (p.textContent || '').trim())
                        .filter(Boolean)
                    if (paragraphs.length > 0) {
                        return paragraphs.join('\n\n')
                    }
                    return (doc.body.textContent || '').replace(/\s+/g, ' ').trim()
                }

                const pickImage = (image) => {
                    if (!image || typeof image !== 'object') {
                        return { url: '', id: '' }
                    }

                    const preferredSize = image.sizes && (
                        image.sizes.thumbnail
                        || image.sizes['gallery-thumbnail']
                        || image.sizes.medium
                        || image.sizes.large
                        || image.sizes.billboard
                    )
                    return {
                        url: (preferredSize && preferredSize.url) || image.url || '',
                        id: image.id || ''
                    }
                }

                const getVolunteerLinkFromCustomFields = (customFields) => {
                    if (!customFields || typeof customFields !== 'object') return ''

                    const isLikelyUrl = (value) => {
                        if (typeof value !== 'string') return false
                        return /^https?:\/\//i.test(value.trim())
                    }

                    // First pass: exact label match to avoid false positives like "Volunteer Perks".
                    for (const key of Object.keys(customFields)) {
                        const field = customFields[key]
                        if (!field || typeof field !== 'object') continue

                        const label = (field.label || '').toString().trim().toLowerCase()
                        const value = (field.value || '').toString().trim()

                        if (label === 'volunteer link' && isLikelyUrl(value)) {
                            return value
                        }
                    }

                    // Fallback: look for a volunteer-labeled field that is actually a URL.
                    for (const key of Object.keys(customFields)) {
                        const field = customFields[key]
                        if (!field || typeof field !== 'object') continue

                        const label = (field.label || '').toString().trim().toLowerCase()
                        const value = (field.value || '').toString().trim()

                        if (!value) continue
                        if (label.includes('volunteer') && isLikelyUrl(value)) {
                            return value
                        }
                    }

                    return ''
                }

                const getOrganizerUrl = (organizer) => {
                    if (!organizer) return ''

                    const normalizeLink = (value) => {
                        if (typeof value !== 'string') return ''
                        return value.trim()
                    }

                    const getPreferredOrganizerLink = (entry) => {
                        if (!entry || typeof entry !== 'object') return ''
                        return normalizeLink(entry.website) || normalizeLink(entry.url)
                    }

                    if (Array.isArray(organizer)) {
                        for (const person of organizer) {
                            const preferredLink = getPreferredOrganizerLink(person)
                            if (preferredLink) {
                                return preferredLink
                            }
                        }
                        return ''
                    }

                    return getPreferredOrganizerLink(organizer)
                }

                const ensureButtonRows = (desiredCount) => {
                    let rows = elParent.querySelectorAll('.ia-email-event-button-wrapper')

                    while (rows.length < desiredCount) {
                        const addBtn = rows[rows.length - 1].querySelector('.ia-email-button-add')
                        createEventButtons(addBtn)
                        rows = elParent.querySelectorAll('.ia-email-event-button-wrapper')
                    }

                    while (rows.length > desiredCount) {
                        rows[rows.length - 1].remove()
                        rows = elParent.querySelectorAll('.ia-email-event-button-wrapper')
                    }

                    return rows
                }

                const eventTitle = getPlainTextValue(data.title)
                const newsletterTitle = formatNewsletterEventTitle(data, eventTitle)
                const selectedImage = pickImage(promotionalImage && (promotionalImage.id || promotionalImage.url)
                    ? promotionalImage
                    : data.image)
                const volunteerLink = getVolunteerLinkFromCustomFields(data.custom_fields)
                const organizerUrl = getOrganizerUrl(data.organizer)
                const eventUrl = (typeof data.url === 'string' && data.url.trim()) ? data.url.trim() : ''
                const eventButtonLink = (typeof data.website === 'string' && data.website.trim())
                    ? data.website.trim()
                    : eventUrl
                const primaryImageLink = volunteerLink || eventButtonLink || ''

                const buttonConfigs = []
                if (volunteerLink) {
                    buttonConfigs.push({ text: 'Volunteer', link: volunteerLink })
                    if (eventButtonLink) {
                        buttonConfigs.push({ text: 'Event', link: eventButtonLink })
                    }
                } else {
                    const fallbackLink = data.website || eventUrl || ''
                    const fallbackText = data.website ? 'Learn More' : 'Volunteer'
                    buttonConfigs.push({ text: fallbackText, link: fallbackLink })
                }

                if (organizerUrl) {
                    buttonConfigs.push({ text: 'Organizer', link: organizerUrl })
                }

                rowLabel.textContent = toPlainText(newsletterTitle)
                elHeader.value = newsletterTitle
                elMute.checked = false
                handleMute(elMute)
                if (elImages.length > 1) {
                    elImages[0].querySelector('.ia-email-event-image-preview').src = selectedImage.url
                    elImages[0].querySelector('.ia-email-event-image-image-id').value = selectedImage.id
                    elImages[0].querySelector('.ia-email-event-image-link').value = primaryImageLink
                    syncRemoveImageVisibility(elImages[0])
                    elImages[1].remove()
                } else {
                    elImages[0].querySelector('.ia-email-event-image-preview').src = selectedImage.url
                    elImages[0].querySelector('.ia-email-event-image-image-id').value = selectedImage.id
                    elImages[0].querySelector('.ia-email-event-image-link').value = primaryImageLink
                    syncRemoveImageVisibility(elImages[0])
                }
                elTwoImages.checked = false

                const excerptRaw = getRenderedValue(data.excerpt)
                const excerptText = getMeaningfulText(excerptRaw) ? excerptRaw : ''
                const descriptionRaw = getRenderedValue(data.description)
                const fallbackDescription = cleanseDescription(descriptionRaw)
                const nextText = excerptText || fallbackDescription

                elText.value = nextText
                if (elText.id && typeof tinyMCE !== 'undefined') {
                    const editor = tinyMCE.get(elText.id)
                    if (editor) editor.setContent(nextText)
                }

                const resolvedButtons = buttonConfigs.length > 0 ? buttonConfigs : [{ text: '', link: '' }]
                const buttonRows = ensureButtonRows(resolvedButtons.length)

                resolvedButtons.forEach((button, index) => {
                    const row = buttonRows[index]
                    row.querySelector('[name="ia-email-events[][event-button][text][]"]').value = button.text
                    row.querySelector('[name="ia-email-events[][event-button][link][]"]').value = button.link
                })
            })
        } else {
            rowLabel.textContent = ''
            elHeader.value = ''
            if (elImages.length > 1) {
                elImages[0].querySelector('.ia-email-event-image-preview').src = ''
                elImages[0].querySelector('.ia-email-event-image-image-id').value = ''
                elImages[0].querySelector('.ia-email-event-image-link').value = ''
                syncRemoveImageVisibility(elImages[0])
                elImages[1].remove()
            } else {
                elImages[0].querySelector('.ia-email-event-image-preview').src = ''
                elImages[0].querySelector('.ia-email-event-image-image-id').value = ''
                elImages[0].querySelector('.ia-email-event-image-link').value = ''
                syncRemoveImageVisibility(elImages[0])
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
            let selectRemoveImageButtons = row.querySelectorAll('.ia-email-remove-image')
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
                    imgWrapClone.querySelector('.ia-email-event-image-image-id').value = ''
                    imgWrapClone.querySelector('.ia-email-event-image-link').value = ''
                    initSelectImage(imgWrapClone.querySelector('.ia-email-select-image'))
                    initRemoveImage(imgWrapClone.querySelector('.ia-email-remove-image'))
                    syncRemoveImageVisibility(imgWrapClone)
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
                    row.dataset.dirty = 'true'
                    markUnsavedChanges()
                })
            })

            selectEventButtonRemove.forEach((e, i) => {
                e.addEventListener('click', (f) => {
                    f.preventDefault()
                    if (i > 0) {
                        //e.parentElement.parentElement.remove()
                        e.parentElement.parentElement.querySelector('.event-button-text').value = 'delete';
                        e.parentElement.parentElement.style.display = 'none';
                        row.dataset.dirty = 'true'
                        markUnsavedChanges()
                    }
                })
            })

            initSelectImage(selectImageButton)
            selectRemoveImageButtons.forEach((button) => initRemoveImage(button))
            row.querySelectorAll('.ia-email-event-image-wrapper').forEach((wrapper) => syncRemoveImageVisibility(wrapper))
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
        markUnsavedChanges()
        initDraggable(el)
        el.querySelector('.ia-email-event-image-preview').src = ''
        el.querySelector('.ia-email-event-image-id').value = ''
        //CLB 1/25/25 - incremental saves
        el.querySelector('.ia-email-event-id').value = ''
        el.querySelector('.ia-email-event-image-image-id').value = ''
        el.querySelector('.ia-email-event-image-link').value = ''
        //CLB 1/25/25 - incremental saves

        let imageButton = el.querySelector('.ia-email-select-image')
        let removeImageButton = el.querySelector('.ia-email-remove-image')
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
        initRemoveImage(removeImageButton)
        el.querySelectorAll('.ia-email-event-image-wrapper').forEach((wrapper) => syncRemoveImageVisibility(wrapper))

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
                imgWrapClone.querySelector('.ia-email-event-image-id').value = ''
                imgWrapClone.querySelector('.ia-email-event-image-preview').src = ''
                imgWrapClone.querySelector('.ia-email-event-image-image-id').value = ''
                imgWrapClone.querySelector('.ia-email-event-image-link').value = ''
                initSelectImage(imgWrapClone.querySelector('.ia-email-select-image'))
                initRemoveImage(imgWrapClone.querySelector('.ia-email-remove-image'))
                syncRemoveImageVisibility(imgWrapClone)
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
                syncRemoveImageVisibility(el.closest('.ia-email-event-image-wrapper'))
                wp.media.model.settings.post.id = wp_media_post_id
                const dirtyRow = el.closest('.ia-email-events-row')
                if (dirtyRow) dirtyRow.dataset.dirty = 'true'
                markUnsavedChanges()
            })
            file_frame.open()
        })
    }

    function initPromotionalImagePicker(el) {
        let file_frame
        const wrapper = el.closest('.ia-email-event-promotional-photo')
        const preview = wrapper.querySelector('.ia-email-event-promotional-photo-preview')
        const imageId = wrapper.querySelector('.ia-email-promotional-image-id')
        const removeButton = wrapper.querySelector('.ia-email-remove-promotional-image')

        el.addEventListener('click', (e) => {
            e.preventDefault()

            if (!file_frame) {
                file_frame = wp.media({
                    title: 'Set promotional photo',
                    button: { text: 'Use this photo' },
                    multiple: false
                })

                file_frame.on('select', () => {
                    const attachment = file_frame.state().get('selection').first().toJSON()
                    preview.src = attachment.url
                    imageId.value = attachment.id
                    removeButton.style.display = ''
                })
            }

            file_frame.open()
        })

        removeButton.addEventListener('click', (e) => {
            e.preventDefault()
            preview.src = ''
            imageId.value = ''
            removeButton.style.display = 'none'
        })
    }

    function initEventEmojiPicker() {
        const emojiInput = document.querySelector('#ia_email_event_emoji')
        const customEmojiInput = document.querySelector('#ia_email_event_emoji_custom')
        if (!emojiInput) return

        if (customEmojiInput) {
            customEmojiInput.addEventListener('input', () => {
                emojiInput.value = customEmojiInput.value.trim()
                document.querySelectorAll('.ia-email-event-emoji-option').forEach((option) => {
                    option.classList.toggle('selected', option.dataset.emoji === emojiInput.value)
                })
            })
        }

        document.querySelectorAll('.ia-email-event-emoji-option').forEach((button) => {
            button.addEventListener('click', () => {
                emojiInput.value = button.dataset.emoji || ''
                if (customEmojiInput) customEmojiInput.value = emojiInput.value
                document.querySelectorAll('.ia-email-event-emoji-option').forEach((option) => option.classList.remove('selected'))
                button.classList.add('selected')
            })
        })
    }

    function formatNewsletterEventTitle(data, eventTitle) {
        const emoji = typeof data.ia_email_event_emoji === 'string' ? data.ia_email_event_emoji.trim() : ''
        const dateText = formatEventDates(data)
        const venue = data.venue && typeof data.venue.venue === 'string' ? data.venue.venue.trim() : ''
        return [emoji + ' ' + eventTitle.trim(), dateText, venue].filter(Boolean).join(', ')
    }

    function formatEventDates(data) {
        if (data.all_day) {
            return formatEventDate(data.start_date_details) + (isDifferentEventDay(data) ? '-' + formatEventDate(data.end_date_details) : '')
        }

        const startDate = formatEventDate(data.start_date_details)
        const endDate = formatEventDate(data.end_date_details)
        const startTime = formatEventTime(data.start_date_details, true)
        const endTime = formatEventTime(data.end_date_details, true)
        if (!startDate || !startTime) return ''
        if (!isDifferentEventDay(data)) {
            const sameMeridiem = getEventMeridiem(data.start_date_details) === getEventMeridiem(data.end_date_details)
            const displayStartTime = sameMeridiem ? formatEventTime(data.start_date_details, false) : startTime
            return startDate + ' ' + displayStartTime + '-' + endTime
        }
        return startDate + ' ' + startTime + '-' + endDate + ' ' + endTime
    }

    function formatEventDate(details) {
        if (!details || !details.year || !details.month || !details.day) return ''
        const date = new Date(Number(details.year), Number(details.month) - 1, Number(details.day))
        const weekdays = ['Sun', 'Mon', 'Tues', 'Wed', 'Thurs', 'Fri', 'Sat']
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec']
        const day = Number(details.day)
        const suffix = day % 100 >= 11 && day % 100 <= 13 ? 'th' : ({ 1: 'st', 2: 'nd', 3: 'rd' }[day % 10] || 'th')
        return weekdays[date.getDay()] + ' ' + months[date.getMonth()] + ' ' + day + suffix
    }

    function formatEventTime(details, includeMeridiem) {
        if (!details || details.hour === undefined || details.minutes === undefined) return ''
        const hour = Number(details.hour)
        const minutes = String(details.minutes).padStart(2, '0')
        const displayHour = hour % 12 || 12
        return displayHour + ':' + minutes + (includeMeridiem ? getEventMeridiem(details) : '')
    }

    function getEventMeridiem(details) {
        return Number(details.hour) >= 12 ? 'pm' : 'am'
    }

    function isDifferentEventDay(data) {
        const start = data.start_date_details
        const end = data.end_date_details
        return !start || !end || start.year !== end.year || start.month !== end.month || start.day !== end.day
    }

    function syncRemoveImageVisibility(imageWrapper) {
        if (!imageWrapper) return
        const removeButton = imageWrapper.querySelector('.ia-email-remove-image')
        const imageIdInput = imageWrapper.querySelector('.ia-email-event-image-image-id')
        if (!removeButton || !imageIdInput) return

        removeButton.style.display = imageIdInput.value ? '' : 'none'
    }

    function initRemoveImage(el) {
        if (!el) return

        el.addEventListener('click', (e) => {
            e.preventDefault()

            const imageWrapper = el.closest('.ia-email-event-image-wrapper')
            if (!imageWrapper) return

            imageWrapper.querySelector('.ia-email-event-image-preview').src = ''
            imageWrapper.querySelector('.ia-email-event-image-image-id').value = ''
            imageWrapper.querySelector('.ia-email-event-image-link').value = ''
            syncRemoveImageVisibility(imageWrapper)

            const row = el.closest('.ia-email-events-row')
            if (row) {
                row.dataset.dirty = 'true'
                const imageWrappers = row.querySelectorAll('.ia-email-event-image-wrapper')
                const isSecondaryImage = imageWrappers.length > 1 && imageWrappers[0] !== imageWrapper
                if (isSecondaryImage) {
                    imageWrapper.remove()
                    const twoImagesToggle = row.querySelector('[name="ia-email-events[][event-two-imgs]"]')
                    if (twoImagesToggle) twoImagesToggle.checked = false
                }
            }

            markUnsavedChanges()
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
        const dirtyRow = el.closest('.ia-email-events-row')
        let newBtnRow = document.createElement('div')
        newBtnRow.classList.add('ia-email-event-button-wrapper')
        newBtnRow.innerHTML = document.querySelector('.ia-email-event-button-wrapper').innerHTML
        newBtnRow.querySelectorAll('input').forEach((e) => {
            e.value = ''
        })
        elParent.after(newBtnRow)
        if (dirtyRow) dirtyRow.dataset.dirty = 'true'
        markUnsavedChanges()
        let newAddBtn = newBtnRow.querySelector('.ia-email-button-add')
        let newRemBtn = newBtnRow.querySelector('.ia-email-button-remove')
        newAddBtn.addEventListener('click', (e) => {
            e.preventDefault()
            createEventButtons(newAddBtn)
        })
        newRemBtn.addEventListener('click', (e) => {
            e.preventDefault()
            newBtnRow.remove()
            if (dirtyRow) dirtyRow.dataset.dirty = 'true'
            markUnsavedChanges()
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
            const plainHeader = toPlainText(parentEl.querySelector('.event-row-header').value)
            parentEl.querySelector('.ia-email-events-row-header-label').textContent = 'Divider: ' + plainHeader;
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
    const dropIndicator = document.createElement('div')
    dropIndicator.className = 'ia-email-drop-indicator'

    eventsWrapper.addEventListener('dragover', (e) => {
        e.preventDefault()
        const targetRow = e.target.closest('.ia-email-events-row')
        if (targetRow && targetRow !== draggedRow) {
            const rect = targetRow.getBoundingClientRect()
            if (e.clientY < rect.top + rect.height / 2) {
                targetRow.before(dropIndicator)
            } else {
                targetRow.after(dropIndicator)
            }
        }
    })

    eventsWrapper.addEventListener('dragleave', (e) => {
        if (!eventsWrapper.contains(e.relatedTarget)) {
            dropIndicator.remove()
        }
    })

    eventsWrapper.addEventListener('drop', (e) => {
        e.preventDefault()
        if (draggedRow && dropIndicator.parentNode) {
            dropIndicator.before(draggedRow)
        }
        dropIndicator.remove()
    })

    function initDraggable(row) {
        const header = row.querySelector('.ia-email-events-row-header')
        if (!header) return

        header.setAttribute('draggable', 'true')
        // Buttons inside the header should remain clickable, not draggable
        header.querySelectorAll('button').forEach(btn => btn.setAttribute('draggable', 'false'))

        header.addEventListener('dblclick', (e) => {
            if (e.target.closest('.ia-email-events-row-buttons')) return

            const muteToggle = row.querySelector('[name="ia-email-events[][event-mute]"]')
            if (!muteToggle) return

            muteToggle.checked = !muteToggle.checked
            handleMute(muteToggle)
            row.dataset.dirty = 'true'
            markUnsavedChanges()
        })

        header.addEventListener('dragstart', (e) => {
            draggedRow = row
            e.dataTransfer.effectAllowed = 'move'
            e.dataTransfer.setData('text/plain', '')

            const clone = header.cloneNode(true)
            const cloneButtons = clone.querySelector('.ia-email-events-row-buttons')
            if (cloneButtons) cloneButtons.remove()
            Object.assign(clone.style, {
                position:            'absolute',
                top:                 '-9999px',
                left:                '-9999px',
                display:             'grid',
                gridTemplateColumns: '100px 1fr',
                alignItems:          'center',
                gap:                 '0.5rem',
                backgroundColor:     '#ddd',
                padding:             '0.3rem 0.75rem',
                borderRadius:        '0.25rem',
                border:              '1px solid #8c8f94',
                margin:              '0',
                width:               'max-content',
                maxWidth:            '420px'
            })
            document.body.appendChild(clone)
            e.dataTransfer.setDragImage(clone, clone.offsetWidth / 2, clone.offsetHeight / 2)
            setTimeout(() => clone.remove(), 0)

            setTimeout(() => row.classList.add('ia-email-row-dragging'), 0)
        })

        header.addEventListener('dragend', () => {
            row.classList.remove('ia-email-row-dragging')
            dropIndicator.remove()
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
                    e.editor.on('Change', () => {
                        row.dataset.dirty = 'true'
                        markUnsavedChanges()
                    })
                }
            }
        })
    }

    form.addEventListener('submit', () => {
        hasUnsavedChanges = false
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
        navigator.clipboard.writeText(getEmailHtml())
        document.querySelector('#copy-code').classList.add('green-pulse')
    })

    document.querySelector('#copy-substack').addEventListener('click', async (e) => {
        e.preventDefault()
        const button = e.currentTarget
        const documentJson = JSON.stringify(getSubstackDocument(), null, 2)
        try {
            await navigator.clipboard.writeText(documentJson)
        } catch (err) {
            const holder = document.createElement('div')
            holder.textContent = documentJson
            holder.style.position = 'fixed'
            holder.style.left = '-9999px'
            document.body.appendChild(holder)
            const range = document.createRange()
            range.selectNodeContents(holder)
            const selection = window.getSelection()
            selection.removeAllRanges()
            selection.addRange(range)
            document.execCommand('copy')
            selection.removeAllRanges()
            holder.remove()
        }
        button.classList.add('green-pulse')
    })

    document.querySelector('#the-code').textContent = getEmailHtml()

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