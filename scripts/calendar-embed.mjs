// The only external asset allowed by the public-page builders is this calendar.
export function withoutCalendarEmbed(html, page) {
  return html.replace(/<iframe\b[^>]*><\/iframe>/g, frame => {
    const source = frame.match(/\bsrc="([^"]+)"/);
    if (page !== 'calendrier.html' || !frame.includes('class="club-calendar"') || !source || /\son[a-z]+\s*=/i.test(frame)) throw new Error('Cadre externe non autorisé.');
    const url = new URL(source[1].replaceAll('&amp;', '&'));
    const allowed = new Set(['src', 'ctz', 'hl', 'mode', 'showPrint', 'showCalendars']);
    if (url.origin !== 'https://calendar.google.com' || url.pathname !== '/calendar/embed' || url.hash || !/^[A-Za-z0-9._+%-]+@[A-Za-z0-9.-]+$/.test(url.searchParams.get('src') ?? '') || url.searchParams.get('ctz') !== 'Europe/Paris' || [...url.searchParams.keys()].some(key => !allowed.has(key))) throw new Error('Source du calendrier non autorisée.');
    return '';
  });
}
