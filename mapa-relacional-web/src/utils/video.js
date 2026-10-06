export function getVideoSource(url) {
  const value = String(url || '').trim()

  if (!value) return null

  try {
    const parsed = new URL(value)
    const hostname = parsed.hostname.toLowerCase().replace(/^www\./, '')

    if (hostname === 'youtu.be') {
      const id = parsed.pathname.split('/').filter(Boolean)[0]

      return validYoutubeId(id)
        ? {
            kind: 'embed',
            provider: 'youtube',
            url: `https://www.youtube-nocookie.com/embed/${id}`,
          }
        : null
    }

    if (
      hostname === 'youtube.com' ||
      hostname === 'm.youtube.com' ||
      hostname === 'youtube-nocookie.com'
    ) {
      const watchId = parsed.searchParams.get('v')

      if (validYoutubeId(watchId)) {
        return {
          kind: 'embed',
          provider: 'youtube',
          url: `https://www.youtube-nocookie.com/embed/${watchId}`,
        }
      }

      const match = parsed.pathname.match(
        /^\/(?:embed|shorts|live)\/([A-Za-z0-9_-]{6,})/,
      )

      if (match) {
        return {
          kind: 'embed',
          provider: 'youtube',
          url: `https://www.youtube-nocookie.com/embed/${match[1]}`,
        }
      }
    }

    if (
      hostname === 'vimeo.com' ||
      hostname === 'player.vimeo.com'
    ) {
      const match = parsed.pathname.match(/\/(?:video\/)?([0-9]+)/)

      if (match) {
        return {
          kind: 'embed',
          provider: 'vimeo',
          url: `https://player.vimeo.com/video/${match[1]}`,
        }
      }
    }

    return {
      kind: 'file',
      provider: 'direct',
      url: value,
    }
  } catch {
    return null
  }
}

function validYoutubeId(value) {
  return /^[A-Za-z0-9_-]{6,}$/.test(String(value || ''))
}
