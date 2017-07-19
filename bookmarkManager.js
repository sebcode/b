;(function() {
  const contentEl = document.getElementById('content')
  const formEl = document.getElementById('filterform')
  const queryEl = document.getElementById('query')

  async function request(data) {
    const res = await fetch('', {
      method: 'POST',
      contentType: 'application/json; charset=utf-8',
      body: JSON.stringify(data),
    })

    const text = await res.text()
    return JSON.parse(text)
  }

  async function deleteEntry(id) {
    const res = await request({ action: 'delete', id })

    if (res.message) {
      alert(res.message)
    }

    if (res.result === true) {
      document.getElementById(`entry_${res.id}`).remove()
    }
  }

  async function setTitle(id, title) {
    const res = await request({ action: 'settitle', id, title })

    if (res.message) {
      alert(res.message)
    }

    if (res.result === true) {
      const el = document.getElementById(`entry_${res.id}`)
      el.querySelector('.title').innerHTML = res.title
      el.setAttribute('data-title', res.rawTitle)
      el.querySelector('.tags').innerHTML = res.tags
    } else {
      alert('err')
    }
  }

  async function setLink(id, link) {
    const res = await request({ action: 'setlink', id, link })

    if (res.message) {
      alert(res.message)
    }

    if (res.result === true) {
      const el = document.getElementById(`entry_${res.id}`)
      const linkEl = el.querySelector('.link')
      linkEl.innerHTML = res.link
      linkEl.setAttribute('href', res.link)
    } else {
      alert('err')
    }
  }

  async function addUrl(url, force) {
    queryEl.setAttribute('disabled', 'disabled')

    const res = await request({
      action: 'add',
      url,
      force: force ? '1' : '0',
    })

    if (!res.force && res.message === 'could not fetch') {
      if (confirm('could not fetch, add anyway?')) {
        addUrl(res.url, true)
        return
      }

      queryEl.removeAttribute('disabled')
      queryEl.focus()
      return
    }

    if (res.message) {
      alert(res.message)
    }

    if (res.result === true) {
      document.location.reload()
    }

    queryEl.removeAttribute('disabled')
    queryEl.focus()
  }

  contentEl.addEventListener('click', e => {
    const target = e.target

    if (target && target.className === 'title') {
      const id = target.parentNode.getAttribute('data-id'),
        rawTitle = target.parentNode.getAttribute('data-title'),
        ret = prompt('Rename or enter "-" to delete', rawTitle)

      if (!ret) {
        return
      }

      if (ret === '-') {
        if (confirm('Really delete?')) {
          deleteEntry(id)
          return
        } else {
          return
        }
      }

      setTitle(id, ret)
    }
  })

  contentEl.addEventListener('dblclick', e => {
    const target = e.target

    if (target && target.className === 'entry') {
      const id = target.getAttribute('data-id')
      const href = target.querySelector('.link').getAttribute('href')
      const ret = prompt('Edit bookmark', href)

      if (ret === null) {
        return
      }

      setLink(id, ret)
    }
  })

  formEl.addEventListener('submit', e => {
    const query = queryEl.value

    e.preventDefault()

    if (query.indexOf('http:') === 0 || query.indexOf('https:') === 0) {
      addUrl(query)
      return false
    }

    document.location.href = '?filter=' + encodeURIComponent(query)

    return false
  })

  let loadingMore = false
  let ifStep = window.infiniteScrolling
  let ifSkip = ifStep

  async function loadMore() {
    if (loadingMore) {
      return
    }

    loadingMore = true

    const url =
      `?filter=${encodeURIComponent(window.filter)}` +
      `&format=html&count=${ifStep}&skip=${ifSkip}`
    const res = await fetch(url)
    const text = await res.text()

    if (!text) {
      return
    }

    contentEl.insertAdjacentHTML('beforeend', text)
    ifSkip += ifStep
    loadingMore = false
  }

  if (window.infiniteScrolling) {
    window.addEventListener('scroll', e => {
      const offset =
        document.body.offsetHeight - (window.pageYOffset + window.innerHeight)

      if (offset < 500) {
        loadMore()
      }
    })
  }
})()

// vim: et ts=2 sw=2 sts=2
