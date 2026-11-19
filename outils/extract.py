"""Extrait le contenu editorial de chaque page telechargee -> work/content.json"""
import sys, os, re, json
from bs4 import BeautifulSoup, NavigableString

SRC, OUT = sys.argv[1], sys.argv[2]
BASE = 'https://laframboisedigitale.fr'
KEEP = {'strong', 'b', 'em', 'i', 'a', 'br', 'u'}


def has(el, cls):
    return cls in (el.get('class') or [])


def clean_inline(el):
    """HTML inline simplifie (strong/em/a/br)."""
    out = []
    for n in el.children:
        if isinstance(n, NavigableString):
            out.append(str(n).replace('<', '&lt;'))
        elif n.name in KEEP:
            inner = clean_inline(n)
            if n.name == 'br':
                out.append('<br>')
            elif n.name == 'a':
                href = (n.get('href') or '').replace('http://laframboisedigitale.fr', BASE)
                out.append('<a href="%s">%s</a>' % (href.replace('"', '&quot;'), inner))
            elif inner.strip():
                tag = {'b': 'strong', 'i': 'em'}.get(n.name, n.name)
                out.append('<%s>%s</%s>' % (tag, inner, tag))
        else:
            out.append(clean_inline(n))
    s = ''.join(out)
    return re.sub(r'[ \t\r\n\xa0]+', ' ', s)


def norm(s):
    s = re.sub(r'(<br>\s*)+$', '', s.strip())
    s = re.sub(r'^(\s*<br>)+', '', s)
    return s.strip()


def text_of(html):
    return re.sub(r'\s+', ' ', re.sub(r'<[^>]+>', ' ', html)).strip()


def fix(u):
    if not u:
        return u
    u = u.replace('http://laframboisedigitale.fr', BASE)
    return u


def best_src(img):
    src = img.get('data-src') or img.get('src') or ''
    ss = img.get('srcset') or img.get('data-srcset') or ''
    best, bw = src, 0
    for part in ss.split(','):
        bits = part.strip().split()
        if len(bits) == 2 and bits[1].endswith('w'):
            try:
                w = int(bits[1][:-1])
            except ValueError:
                continue
            if w > bw:
                best, bw = bits[0], w
    # version d'origine (sans suffixe -WxH)
    best = re.sub(r'-\d+x\d+(\.(?:png|jpe?g|webp))$', r'\1', best)
    return fix(best)


def walk(el, items):
    for n in el.children:
        if isinstance(n, NavigableString):
            continue
        cls = n.get('class') or []
        if n.name in ('script', 'style', 'noscript', 'svg'):
            continue
        if 'wp-block-kubio-heading' in cls or (n.name in ('h1', 'h2', 'h3', 'h4', 'h5', 'h6') and not n.find_parent(class_='wp-block-kubio-heading')):
            h = n if n.name and re.match(r'h[1-6]$', n.name) else n.find(re.compile(r'^h[1-6]$'))
            if h is not None:
                html = norm(clean_inline(h))
                if text_of(html):
                    items.append({'t': 'h', 'l': int(h.name[1]), 'html': html})
                continue
        if 'wp-block-kubio-accordionitem' in cls:
            tt = n.find(class_='wp-block-kubio-accordionitem__titleText') or n.find(class_='wp-block-kubio-accordionitem__title')
            ct = n.find(class_='wp-block-kubio-accordionitem__content')
            sub = []
            if ct is not None:
                walk(ct, sub)
            items.append({'t': 'acc', 'title': text_of(clean_inline(tt)) if tt else '', 'items': sub})
            continue
        if 'wp-block-kubio-iconlistitem' in cls:
            tx = n.find(class_='wp-block-kubio-iconlistitem__text')
            if tx is not None:
                html = norm(clean_inline(tx))
                a = n.find('a')
                if text_of(html):
                    items.append({'t': 'li', 'html': html, 'href': fix(a.get('href')) if a and a.get('href') else ''})
            continue
        if 'wp-block-kubio-text' in cls or n.name == 'p':
            ps = n.find_all('p') if n.name != 'p' else [n]
            if not ps:
                ps = [n]
            for p in ps:
                html = norm(clean_inline(p))
                if text_of(html):
                    items.append({'t': 'p', 'html': html})
            continue
        if n.name in ('ul', 'ol'):
            for li in n.find_all('li'):
                html = norm(clean_inline(li))
                if text_of(html):
                    items.append({'t': 'li', 'html': html, 'href': ''})
            continue
        if 'wp-block-kubio-button' in cls or 'wp-block-kubio-link' in cls:
            a = n if n.name == 'a' else n.find('a')
            if a is not None and text_of(clean_inline(a)):
                items.append({'t': 'btn', 'label': text_of(clean_inline(a)), 'href': fix(a.get('href') or '')})
            continue
        if n.name == 'img':
            s = best_src(n)
            if s and 'data:image' not in s:
                items.append({'t': 'img', 'src': s, 'alt': n.get('alt') or ''})
            continue
        if n.name == 'video' or n.name == 'source':
            s = n.get('src') or (n.find('source').get('src') if n.find('source') else '')
            if s:
                items.append({'t': 'video', 'src': fix(s)})
            continue
        if n.name == 'iframe':
            items.append({'t': 'iframe', 'src': n.get('src') or n.get('data-src') or ''})
            continue
        if n.name == 'form':
            items.append({'t': 'form', 'cls': ' '.join(cls)[:80]})
            continue
        # fond d'image declare en style inline
        st = n.get('style') or ''
        m = re.search(r'background-image:\s*url\(([^)]+)\)', st)
        if m:
            items.append({'t': 'bg', 'src': fix(m.group(1).strip('\'"'))})
        walk(n, items)


def page(path):
    soup = BeautifulSoup(open(path, encoding='utf-8', errors='replace').read(), 'html.parser')
    d = {'title_tag': soup.title.string.strip() if soup.title and soup.title.string else ''}
    md = soup.find('meta', attrs={'name': 'description'})
    d['meta_description'] = md.get('content') if md else ''
    b = soup.body
    d['body_class'] = ' '.join(b.get('class') or [])
    m = re.search(r'page-id-(\d+)', d['body_class'])
    d['id'] = int(m.group(1)) if m else 0
    m = re.search(r'page-template-(\S+)', d['body_class'].replace('page-template ', ''))
    d['tpl'] = m.group(1) if m else ''
    root = soup.find(class_='wp-site-blocks') or b
    secs = []
    # zones : header / contenu / footer
    for zone in root.find_all(recursive=False):
        zname = zone.name
        zcls = ' '.join(zone.get('class') or [])
        items = []
        walk(zone, items)
        secs.append({'zone': zname, 'cls': zcls[:120], 'id': zone.get('id') or '', 'items': items})
    d['zones'] = secs
    return d


res = {}
for fn in sorted(os.listdir(SRC)):
    if fn.endswith('.html'):
        slug = fn[:-5]
        res['' if slug == '_home' else slug] = page(os.path.join(SRC, fn))
json.dump(res, open(OUT, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
for slug, d in res.items():
    print('==', slug or '(home)', '| id', d['id'], '| tpl', d['tpl'], '|', d['title_tag'])
    for z in d['zones']:
        c = {}
        for it in z['items']:
            c[it['t']] = c.get(it['t'], 0) + 1
        print('   zone', z['zone'], z['cls'][:50], c)
