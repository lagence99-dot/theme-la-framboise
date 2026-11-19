"""Construit data/pages.json (contenu en blocs WordPress) a partir de work/content.json."""
import sys, json, re, html

SRC, OUT = sys.argv[1], sys.argv[2]
D = json.load(open(SRC, encoding='utf-8'))
B = 'https://laframboisedigitale.fr'
UP = B + '/wp-content/uploads/'


# ---------- generateurs de blocs ----------
def attrs(a):
    return (' ' + json.dumps(a, ensure_ascii=False, separators=(',', ':')).replace('--', '\\u002d\\u002d')) if a else ''


def amp(s):
    return re.sub(r'&(?!(?:[a-zA-Z]+|#\d+);)', '&amp;', s)


def tidy(h):
    h = amp(h)
    for _ in range(3):
        h = re.sub(r'<strong>\s*<strong>(.*?)</strong>\s*</strong>', r'<strong>\1</strong>', h)
    h = re.sub(r'<(strong|em)>\s*</\1>', '', h)
    return h.strip()


def unbold(h):
    h = tidy(h)
    m = re.fullmatch(r'<strong>(.*)</strong>\.?', h, flags=re.S)
    if m and '<strong>' not in m.group(1):
        h = m.group(1) + ('.' if h.endswith('.') else '')
    h = re.sub(r'</strong>(\s*<br>\s*)<strong>', r'\1', h)
    m = re.fullmatch(r'<strong>(.*)</strong>', h, flags=re.S)
    if m and '<strong>' not in m.group(1):
        h = m.group(1)
    return h


def H(level, inner, cls=''):
    a = {}
    if level != 2:
        a['level'] = level
    if cls:
        a['className'] = cls
    c = 'wp-block-heading' + (' ' + cls if cls else '')
    return '<!-- wp:heading%s -->\n<h%d class="%s">%s</h%d>\n<!-- /wp:heading -->' % (attrs(a), level, c, inner, level)


def P(inner, cls=''):
    if cls:
        return '<!-- wp:paragraph%s -->\n<p class="%s">%s</p>\n<!-- /wp:paragraph -->' % (attrs({'className': cls}), cls, inner)
    return '<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->' % inner


def IMG(src, alt='', caption='', href='', cls=''):
    a = {'sizeSlug': 'full', 'linkDestination': 'custom' if href else 'none'}
    if cls:
        a['className'] = cls
    c = 'wp-block-image size-full' + (' ' + cls if cls else '')
    img = '<img src="%s" alt="%s"/>' % (html.escape(src, True), html.escape(alt, True))
    if href:
        img = '<a href="%s">%s</a>' % (html.escape(href, True), img)
    cap = '<figcaption class="wp-element-caption">%s</figcaption>' % amp(caption) if caption else ''
    return '<!-- wp:image%s -->\n<figure class="%s">%s%s</figure>\n<!-- /wp:image -->' % (attrs(a), c, img, cap)


def VIDEO(src, poster='', cap=''):
    po = ' poster="%s"' % html.escape(poster, True) if poster else ''
    c = '<figcaption class="wp-element-caption">%s</figcaption>' % amp(cap) if cap else ''
    return '<!-- wp:video -->\n<figure class="wp-block-video"><video controls%s src="%s"></video>%s</figure>\n<!-- /wp:video -->' % (po, html.escape(src, True), c)


def split_br(items):
    """Un paragraphe contenant des sauts de ligne devient plusieurs paragraphes."""
    out = []
    for it in items:
        if it['t'] == 'p':
            parts = [x.strip() for x in re.split(r'(?:<br>\s*)+', it['html'])]
            parts = [x for x in parts if re.sub(r'<[^>]+>', '', x).strip()]
            out.extend({'t': 'p', 'html': x} for x in parts)
        else:
            out.append(it)
    return out


def BTNS(pairs):
    inner = ''.join('<!-- wp:button -->\n<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>\n<!-- /wp:button -->' % (html.escape(h, True), amp(l)) for l, h in pairs)
    return '<!-- wp:buttons -->\n<div class="wp-block-buttons">%s</div>\n<!-- /wp:buttons -->' % inner


def LIST(items):
    inner = ''.join('<!-- wp:list-item -->\n<li>%s</li>\n<!-- /wp:list-item -->' % i for i in items)
    return '<!-- wp:list -->\n<ul class="wp-block-list">%s</ul>\n<!-- /wp:list -->' % inner


def DETAILS(title, inner):
    return '<!-- wp:details -->\n<details class="wp-block-details"><summary>%s</summary>%s</details>\n<!-- /wp:details -->' % (amp(title), inner)


def HTMLB(code):
    return '<!-- wp:html -->\n%s\n<!-- /wp:html -->' % code


def SC(code):
    return '<!-- wp:shortcode -->\n%s\n<!-- /wp:shortcode -->' % code


NAMES = {'lfd-hero': 'Ouverture', 'lfd-intro': 'Introduction', 'lfd-feat': 'Texte + visuel', 'lfd-manifesto': 'Manifeste', 'lfd-stack': 'Projets à la une',
         'lfd-panel': 'Projet', 'lfd-statement': 'Grande phrase', 'lfd-exp': 'Expertises', 'lfd-xrow': 'Expertise', 'lfd-cols': 'Colonnes', 'lfd-col': 'Colonne',
         'lfd-gallery': 'Galerie', 'lfd-logos': 'Frise de logos', 'lfd-cover': 'Texte sur image de fond', 'lfd-videoband': 'Vidéo pleine largeur', 'lfd-videos3': 'Vidéos côte à côte', 'lfd-words': 'Mots-clés', 'lfd-mosaic': 'Mosaïque de projets', 'lfd-text': 'Texte', 'lfd-meta': 'Fiche projet', 'lfd-case': 'Présentation du projet', 'lfd-prose': 'Texte légal'}


def G(cls, *inner):
    a = {'metadata': {'name': NAMES[cls.split()[0]]}, 'className': cls}
    return '<!-- wp:group%s -->\n<div class="wp-block-group %s">%s</div>\n<!-- /wp:group -->' % (attrs(a), cls, '\n'.join(i for i in inner if i))


# ---------- projets ----------
PROJ = [
    ('cartier-experience-digitale', 'Cartier', 'Horlogerie', '2026/09/cartier.png'),
    ('dior-jadore-experience-digitale', 'Dior J’adore', 'Parfum', '2026/09/dior.png'),
    ('le-monde-refonte-espace-abonne-paris', 'Le Monde', 'Presse', '2026/09/vignette.png'),
    ('lipton-experience-digitale', 'Lipton', 'Grande consommation', '2026/09/lipton.png'),
    ('chanel-experience-digitale', 'Chanel', 'Parfum', '2026/08/ChatGPT-Image-15-aout-2026-18_48_52.png'),
    ('celine-experience-digitale-ux-ui', 'Celine', 'Mode', '2026/09/celine.png'),
    ('mugler-experience-digitale-ux-ui', 'Mugler', 'Parfum', '2026/09/mugler.png'),
    ('festival-de-cannes-experience-digitale', 'Festival de Cannes', 'Cinéma', '2026/08/08c0e915-b2f9-49a0-9a16-a39a39c6712c.png'),
    ('feadship-experience-digitale-ux-ui', 'Feadship', 'Yachting', '2026/09/7578f5fd-3011-44f8-b6d6-46d896a21218.png'),
    ('renault-captur-experience-digitale-ux', 'Renault Captur', 'Automobile', '2026/09/renaultCaptur.png'),
    ('renault-5-experience-digitale-ux-design', 'Renault 5', 'Automobile', '2026/09/Renault5-1-1.png'),
    ('le-parisien-experience-digitale-ux-ui', 'Le Parisien', 'Presse', '2026/09/leparisien.png'),
    ('sofinco-experience-digitale-ux-ui', 'Sofinco', 'Finance', '2026/09/3833e37d-e66d-40ad-ad12-e4db79c7c56c.png'),
    ('dans-la-brume-electrique-experience-digitale', 'Dans la brume électrique', 'Cinéma', '2026/09/lesbrums.png'),
    ('les-rivieres-pourpres-experience-digitale-ux-ui', 'Les Rivières pourpres', 'Cinéma', '2026/09/les-rivieres.png'),
    ('adidas-experience-digitale-ux-design', 'Adidas', 'Sport', '2026/09/adidas.png'),
]
PSLUG = {p[0]: p for p in PROJ}
# visuel d'ouverture plus propre que la capture d'interface, quand il existe : (fichier, point focal)
HERO_OVERRIDE = {
    'cartier-experience-digitale': ('2026/08/CARTIER2.png', ''),
    'dior-jadore-experience-digitale': ('2026/09/299ef870-2cbb-4303-b487-d1385453102b.png', 'lfd-focus-79-50'),
}


def sections(p):
    out = []
    for z in p['zones']:
        if 'kubio-header' in z['cls'] or 'kubio-footer' in z['cls']:
            continue
        items, seen = [], set()
        for it in z['items']:
            if it['t'] in ('bg', 'img'):
                if it['src'] in seen:
                    continue
                seen.add(it['src'])
            items.append(it)
        if items:
            out.append(items)
    return out


def norm_imgs(items):
    """bg -> img seulement si la section n'a pas d'image."""
    has_img = any(i['t'] == 'img' for i in items)
    res = []
    for i in items:
        if i['t'] == 'bg':
            if not has_img:
                res.append({'t': 'img', 'src': i['src'], 'alt': ''})
                has_img = True
            continue
        res.append(i)
    return res


def flow(items, hshift=0):
    """Rend une suite d'items en blocs simples (ordre conserve)."""
    out, lis = [], []

    def flush():
        if lis:
            out.append(LIST(lis[:]))
            del lis[:]
    for it in items:
        t = it['t']
        if t == 'li':
            h = tidy(it['html'])
            if it.get('href') and '<a ' not in h:
                h = '<a href="%s">%s</a>' % (html.escape(it['href'], True), h)
            lis.append(h)
            continue
        flush()
        if t == 'h':
            out.append(H(min(6, max(1, it['l'] + hshift)), unbold(it['html'])))
        elif t == 'p':
            out.append(P(tidy(it['html'])))
        elif t == 'img':
            out.append(IMG(it['src'], it.get('alt', '')))
        elif t == 'btn':
            out.append(BTNS([(it['label'], it['href'])]))
        elif t == 'acc':
            out.append(DETAILS(it['title'], flow(it['items'])))
        elif t == 'video':
            out.append(VIDEO(it['src']))
        elif t == 'iframe':
            out.append(HTMLB('<iframe src="%s" loading="lazy" title="Carte" style="width:100%%;height:420px;border:0"></iframe>' % html.escape(it['src'], True)))
    flush()
    return '\n'.join(out)


def classify(items):
    ts = [i['t'] for i in items]
    imgs = [i for i in items if i['t'] == 'img']
    texty = [i for i in items if i['t'] not in ('img', 'video')]
    if not texty:
        if len(imgs) >= 5:
            return 'logos'
        return 'gallery'
    if 'acc' in ts:
        return 'text'
    if len(imgs) >= 2:
        # cartes : chaque image suivie d'un titre
        idx = [k for k, i in enumerate(items) if i['t'] == 'img']
        if all(k + 1 < len(items) and items[k + 1]['t'] == 'h' for k in idx):
            return 'cards'
        return 'text'
    subs = [k for k, i in enumerate(items) if i['t'] == 'h' and i['l'] >= 3]
    if not imgs and len(subs) >= 2 and all(k + 1 < len(items) and items[k + 1]['t'] == 'p' for k in subs):
        first = subs[0]
        lead = items[:first]
        if all(i['t'] in ('h', 'p') for i in lead) and all(i['t'] in ('h', 'p') for i in items):
            # strictement [h3, p+] repetes
            ok = True
            for a, b in zip(subs, subs[1:] + [len(items)]):
                if b - a < 2:
                    ok = False
            if ok and len(subs) <= 4:
                return 'cols'
    if len(imgs) == 1:
        return 'feat'
    return 'text'


# Pages expertise : visuels de references a glisser dans la page (fichier, client, categorie, page projet)
R = {
    'dior-sq': ('2026/08/dior-carre.png', 'Dior J’adore', 'Parfum', 'dior-jadore-experience-digitale'),
    'chanel-sq': ('2026/08/chanel-carre.png', 'Chanel', 'Parfum', 'chanel-experience-digitale'),
    'cartier-sq': ('2026/08/cartier-carre.png', 'Cartier', 'Horlogerie', 'cartier-experience-digitale'),
    'lipton-sq': ('2026/08/lipton-Carre.png', 'Lipton', 'Grande consommation', 'lipton-experience-digitale'),
    'celine': ('2026/09/celine.png', 'Celine', 'Mode', 'celine-experience-digitale-ux-ui'),
    'mugler': ('2026/09/mugler.png', 'Mugler', 'Parfum', 'mugler-experience-digitale-ux-ui'),
    'ott': ('2026/09/ott.png', 'Ott', 'Vin', ''),
    'dior-ui': ('2026/09/6718a5b3-e6e1-4bc3-9de6-8ddb74a58c7a.png', 'Dior J’adore', 'Parfum', 'dior-jadore-experience-digitale'),
    'dior-map': ('2026/09/a3dbfa38-b52a-4b64-b176-541cde4e783f.png', 'Dior J’adore', 'Parfum', 'dior-jadore-experience-digitale'),
    'cartier-ui': ('2026/08/ChatGPT-Image-15-aout-2026-18_48_44-1.png', 'Cartier', 'Horlogerie', 'cartier-experience-digitale'),
    'parisien': ('2026/09/leparisien.png', 'Le Parisien', 'Presse', 'le-parisien-experience-digitale-ux-ui'),
    'sofinco': ('2026/09/3833e37d-e66d-40ad-ad12-e4db79c7c56c.png', 'Sofinco', 'Finance', 'sofinco-experience-digitale-ux-ui'),
    'cartier': ('2026/08/CARTIER2.png', 'Cartier', 'Horlogerie', 'cartier-experience-digitale'),
    'dior': ('2026/09/299ef870-2cbb-4303-b487-d1385453102b.png', 'Dior J’adore', 'Parfum', 'dior-jadore-experience-digitale'),
    'chanel': ('2026/08/ChatGPT-Image-15-aout-2026-18_48_52.png', 'Chanel', 'Parfum', 'chanel-experience-digitale'),
    'rivieres': ('2026/09/les-rivieres.png', 'Les Rivières pourpres', 'Cinéma', 'les-rivieres-pourpres-experience-digitale-ux-ui'),
    'brume': ('2026/09/lesbrums.png', 'Dans la brume électrique', 'Cinéma', 'dans-la-brume-electrique-experience-digitale'),
    'r5': ('2026/09/Renault5-1-1.png', 'Renault 5', 'Automobile', 'renault-5-experience-digitale-ux-design'),
    'captur': ('2026/09/renaultCaptur.png', 'Renault Captur', 'Automobile', 'renault-captur-experience-digitale-ux'),
    'adidas': ('2026/08/ChatGPT-Image-15-aout-2026-a-19_46_57.png', 'Adidas', 'Sport', 'adidas-experience-digitale-ux-design'),
    'cannes': ('2026/08/08c0e915-b2f9-49a0-9a16-a39a39c6712c.png', 'Festival de Cannes', 'Cinéma', 'festival-de-cannes-experience-digitale'),
    'lemonde': ('2026/09/vignette.png', 'Le Monde', 'Presse', 'le-monde-refonte-espace-abonne-paris'),
    'lipton': ('2026/09/lipton.png', 'Lipton', 'Grande consommation', 'lipton-experience-digitale'),
    'yacht': ('2026/09/7578f5fd-3011-44f8-b6d6-46d896a21218.png', 'Feadship', 'Yachting', 'feadship-experience-digitale-ux-ui'),
}
# par page : visuel de fond de la tranche « image en arriere-plan », puis visuels places a gauche / a droite des textes
EXP = {
    'agence-branding-luxe': ('dior', ['chanel-sq', 'celine', 'mugler', 'dior-sq', 'ott']),
    'agence-ux-cx-design-luxe': ('dior', ['dior-ui', 'cartier-ui', 'parisien', 'dior-map', 'sofinco']),
    'direction-artistique-digitale-luxe': ('dior', ['cartier-sq', 'chanel', 'rivieres', 'mugler', 'brume']),
    'developpement-web-mobile-ia-luxe-paris': ('cartier', ['r5', 'adidas', 'captur', 'sofinco', 'cannes']),
    'agence-conseil-en-communication-luxe': ('cartier', ['lemonde', 'lipton', 'parisien', 'cannes', 'chanel-sq']),
    'design-emotionnel-luxe': ('dior', ['dior-sq', 'lipton-sq', 'mugler', 'cartier-sq', 'celine']),
}


def ref_img(key):
    f, client, cat, slug = R[key]
    return IMG(UP + f, 'Référence ' + client, client + ' · ' + cat, href=(L(slug + '/') if slug else ''))


def plain(h):
    return re.sub(r'\s+', ' ', re.sub(r'<[^>]+>', ' ', h)).strip()


def expertise_section(s, st):
    """Tranche de texte d'une page expertise -> image de fond, texte + visuel alterne, aplat, liste de mots-cles."""
    out = []
    # liste de mots-cles (titre fait de lignes courtes) -> aplat framboise
    words = None
    rest = []
    for it in s:
        if words is None and it['t'] == 'h' and it['html'].count('<br>') >= 3 and len(plain(it['html'])) < 260:
            words = [w for w in (plain(x) for x in re.split(r'<br>', it['html'])) if w]
        else:
            rest.append(it)
    s = rest
    texty = [i for i in s if i['t'] in ('h', 'p')]
    # 1re tranche courte (un titre, un paragraphe) -> image en arriere-plan
    if not st['cover'] and len(s) == 2 and s[0]['t'] == 'h' and s[1]['t'] == 'p':
        st['cover'] = True
        f = R[st['bg']]
        out.append(G('lfd-cover', IMG(UP + f[0], '', cls='lfd-focus-79-50' if st['bg'] == 'dior' else ''), flow(s)))
        return out
    if any(i['t'] in ('acc', 'li', 'btn', 'iframe') for i in s) and not texty[:1]:
        out.append(G('lfd-text lfd-band-soft', flow(s)))
        return out
    # decoupe en blocs : un nouveau bloc a chaque titre qui suit du texte
    chunks, cur = [], []
    for it in s:
        if it['t'] == 'h' and any(c['t'] == 'p' for c in cur):
            chunks.append(cur)
            cur = []
        cur.append(it)
    if cur:
        chunks.append(cur)
    for n, ch in enumerate(chunks):
        has_list = any(i['t'] in ('li', 'acc') for i in ch)
        if has_list:
            out.append(G('lfd-text lfd-band-soft', flow(ch)))
        elif st['n'] % 3 == 2 or not st['pool']:
            # une tranche sur trois : aplat sombre, sans visuel, pour rythmer la page
            out.append(G('lfd-text lfd-band-dark', flow(ch)))
            st['n'] += 1
        else:
            out.append(G('lfd-feat' + (' lfd-alt' if st['alt'] else ''), ref_img(st['pool'].pop(0)), flow(ch)))
            st['alt'] = not st['alt']
            st['n'] += 1
        if n == 0 and words:
            out.append(G('lfd-words lfd-band-accent', LIST([amp(w) for w in words])))
            words = None
    if words:
        out.append(G('lfd-words lfd-band-accent', LIST([amp(w) for w in words])))
    return out


def build_generic(slug, p):
    secs = [norm_imgs(s) for s in sections(p)]
    if not secs:
        return None
    blocks = []
    first = secs[0]
    h1s = [i for i in first if i['t'] == 'h' and i['l'] == 1]
    legal = slug.startswith('politique') or slug.startswith('conditions')
    if legal:
        inner = []
        done_h = False
        for s in secs:
            for it in s:
                if it['t'] == 'h':
                    inner.append(H(1 if not done_h else max(2, it['l']), unbold(it['html'])))
                    done_h = True
                elif it['t'] == 'p':
                    parts = [x.strip() for x in re.split(r'(?:<br>\s*){2,}', it['html'])]
                    if len(parts) == 1:
                        parts = [x.strip() for x in re.split(r'<br>\s*(?=<strong>)', it['html'])]
                    for part in parts:
                        if re.sub(r'<[^>]+>', '', part).strip():
                            inner.append(P(tidy(part)))
                elif it['t'] == 'li':
                    inner.append(LIST([tidy(it['html'])]))
        return G('lfd-prose', *inner)
    # --- ouverture ---
    hero_items, rest_first = [], []
    img = next((i for i in first if i['t'] == 'img'), None)
    if img:
        hero_items.append(IMG(img['src'], img.get('alt', '')))
    used_sub = False
    intro_ps = []
    title_done = False
    for it in first:
        if it['t'] == 'h':
            if not title_done:
                hero_items.append(H(1, unbold(it['html'])))
                title_done = True
            elif not used_sub:
                hero_items.append(H(max(2, it['l']), unbold(it['html']), 'lfd-sub'))
                used_sub = True
            else:
                rest_first.append(it)
        elif it['t'] == 'p':
            intro_ps.append(it)
        elif it['t'] not in ('img',):
            rest_first.append(it)
    blocks.append(G('lfd-hero', *hero_items))
    intro_ps = split_br(intro_ps)
    st = None
    if slug in EXP:
        st = {'bg': EXP[slug][0], 'pool': list(EXP[slug][1]), 'alt': False, 'n': 0, 'cover': False}
    if intro_ps:
        lead = P(tidy(intro_ps[0]['html']), 'lfd-big')
        more = [P(tidy(i['html'])) for i in intro_ps[1:]]
        blocks.append(G('lfd-intro', lead, ref_img(st['pool'].pop(0)) if st else '', *more, flow(rest_first)))
    elif rest_first:
        blocks.append(G('lfd-text', flow(rest_first)))
    alt = False
    for s in secs[1:]:
        kind = classify(s)
        if kind == 'gallery':
            blocks.append(G('lfd-gallery', flow(s)))
        elif kind == 'logos':
            blocks.append(G('lfd-logos', flow(s)))
        elif kind == 'feat':
            im = next(i for i in s if i['t'] == 'img')
            others = [i for i in s if i is not im]
            blocks.append(G('lfd-feat' + (' lfd-alt' if alt else ''), IMG(im['src'], im.get('alt', '')), flow(others)))
            alt = not alt
        elif kind == 'cols':
            subs = [k for k, i in enumerate(s) if i['t'] == 'h' and i['l'] >= 3]
            lead = s[:subs[0]]
            cols = []
            for a, b in zip(subs, subs[1:] + [len(s)]):
                cols.append(G('lfd-col', flow(s[a:b])))
            blocks.append(G('lfd-cols' + (' lfd-band-dark' if st else ''), flow(lead), *cols))
        elif kind == 'cards':
            idx = [k for k, i in enumerate(s) if i['t'] == 'img']
            lead = s[:idx[0]]
            cols = []
            for a, b in zip(idx, idx[1:] + [len(s)]):
                cols.append(G('lfd-col', flow(s[a:b])))
            blocks.append(G('lfd-cols lfd-cards', flow(lead), *cols))
        else:
            # cartes d'offres (formations) : decoupe sur les petits sur-titres h6 suivis d'un titre
            cuts = [k for k, i in enumerate(s) if i['t'] == 'h' and i['l'] == 6 and k + 1 < len(s) and s[k + 1]['t'] == 'h']
            if len(cuts) >= 2:
                lead = s[:cuts[0]]
                cols = [G('lfd-col', flow(s[a:b])) for a, b in zip(cuts, cuts[1:] + [len(s)])]
                if lead:
                    blocks.append(G('lfd-text', flow(lead)))
                blocks.append(G('lfd-cols lfd-cards', *cols))
            elif st:
                blocks.extend(expertise_section(s, st))
            else:
                blocks.append(G('lfd-text', flow(s)))
    if slug in EXP or slug == 'agence-design-digital-luxe':
        # deux cesures pleine largeur : la video Cartier, puis un grand visuel a remplacer par une video
        mp4 = UP + '2026/08/Luxury_wristwatch_fades_into_view_202608131701.mp4'
        band1 = G('lfd-videoband', VIDEO(mp4, UP + '2026/08/CARTIER2.png'))
        band2 = G('lfd-videoband', IMG(UP + '2026/09/299ef870-2cbb-4303-b487-d1385453102b.png', 'Dior J’adore', cls='lfd-focus-79-50'))
        blocks.insert(2, band1)
        blocks.insert(max(3, (len(blocks) * 2) // 3), band2)
        if slug == 'agence-design-digital-luxe':
            row = G('lfd-videos3',
                    VIDEO(mp4, UP + '2026/08/CARTIER2.png', 'Cartier, Panthère de Cartier'),
                    IMG(UP + '2026/08/dior-carre.png', 'Dior J’adore', 'Dior, J’adore'),
                    IMG(UP + '2026/08/chanel-carre.png', 'Bleu de Chanel', 'Chanel, Bleu de Chanel'),
                    IMG(UP + '2026/08/lipton-Carre.png', 'Lipton', 'Lipton, Yellow Label'))
            blocks.insert(len(blocks) - 1, row)
    return '\n\n'.join(blocks)


def build_project(slug, p):
    secs = [norm_imgs(s) for s in sections(p)]
    first = secs[0]
    hero_img = next((i for i in first if i['t'] == 'img'), None)
    hero_src, focus = (hero_img['src'] if hero_img else ''), ''
    gallery_extra = []
    if slug in HERO_OVERRIDE:
        if hero_img:
            gallery_extra.append(hero_img)
        hero_src, focus = UP + HERO_OVERRIDE[slug][0], HERO_OVERRIDE[slug][1]
    hs = [i for i in first if i['t'] == 'h']
    ps = [i for i in first if i['t'] == 'p']
    hero = []
    if hero_src:
        hero.append(IMG(hero_src, 'Projet ' + PSLUG[slug][1], cls=focus))
    top = next((i for i in hs if i['l'] == 1), hs[0] if hs else None)
    for i in hs:
        if i is top:
            hero.append(H(1, unbold(i['html'])))
        else:
            hero.append(P(re.sub(r'<[^>]+>', '', tidy(i['html'])), 'lfd-eyebrow'))
    blocks = [G('lfd-hero', *hero)]
    meta, media = '', []
    for s in secs[1:]:
        hh = [i for i in s if i['t'] == 'h']
        if hh and re.sub(r'<[^>]+>', '', hh[0]['html']).strip().lower().startswith('expertise'):
            meta = G('lfd-meta', flow(s))
            continue
        for i in s:
            if i['t'] in ('img', 'video'):
                media.append(i)
            elif i['t'] in ('p', 'h'):
                ps.append(i)
    ps = split_br(ps)
    if ps:
        lead = P(tidy(ps[0]['html']), 'lfd-big')
        blocks.append(G('lfd-case', lead, flow(ps[1:])))
    if meta:
        blocks.append(meta)
    seen, gal = {hero_src}, []
    for i in gallery_extra + media:
        if i['src'] in seen:
            continue
        seen.add(i['src'])
        gal.append(i)
    if gal:
        blocks.append(G('lfd-gallery', flow(gal)))
    return '\n\n'.join(blocks)


def L(path):
    return B + '/' + path


def build_home():
    def panel(n, img, alt, title, text, label, href):
        return G('lfd-panel', IMG(UP + img, alt, href=L(href)), H(2, title), P(text), BTNS([(label, L(href))]))

    def xrow(k, title, href):
        return G('lfd-xrow', P(k, 'lfd-eyebrow'), H(3, '<a href="%s">%s</a>' % (L(href), title)))
    b = []
    b.append(G('lfd-hero',
               IMG(UP + '2026/08/CARTIER2.png', 'Cartier', 'UX et UI pour Cartier. Création de l’interface design de la gamme montre Cartier'),
               IMG(UP + '2026/09/299ef870-2cbb-4303-b487-d1385453102b.png', 'Dior', 'Dior J’adore : UX design et expérience digitale d’un parfum iconique', cls='lfd-focus-79-50'),
               IMG(UP + '2026/09/7578f5fd-3011-44f8-b6d6-46d896a21218.png', 'Feadship', 'Feadship : expérience digitale UX UI', cls='lfd-focus-55-60'),
               P('Agence digitale UX Paris Cannes', 'lfd-eyebrow'),
               H(1, 'Agence digitale luxe à Paris : UX/UI design, branding et sites internet <em>d’exception</em>')))
    b.append(G('lfd-intro',
               P('<strong>Nous sommes une agence digitale spécialisée en design, UX et UI</strong>, basée à <strong>Paris et Cannes</strong>. Nous concevons des <strong>sites internet haut de gamme</strong> et des <strong>expériences digitales sur mesure</strong> pour les marques qui refusent de ressembler aux autres.', 'lfd-big'),
               IMG(UP + '2026/09/mugler.png', 'Interface Mugler, flacon Angel', 'Mugler · Référence parfum', href=L('mugler-experience-digitale-ux-ui/')),
               P('Nous accompagnons les entreprises dans la création de leur <strong>identité visuelle</strong>, la <strong>conception ou la refonte de leur site web</strong> et la construction d’<strong>univers graphiques forts</strong>.'),
               BTNS([('Discuter de votre projet', L('contact/')), ('Nos projets', L('portfolio-agence-digitale-luxe/'))])))
    b.append(G('lfd-feat',
               IMG(UP + '2026/08/dior-carre.png', 'Flacon Dior J’adore', 'Dior J’adore · Référence parfum', href=L('dior-jadore-experience-digitale/')),
               H(2, 'UX de luxe, <em>unique</em> pour chaque marque'),
               P('Notre conviction : une marque ne doit jamais chercher à ressembler à une autre. Chaque projet possède ses propres codes <strong>UX et UI design,</strong> son propre niveau d’exigence et sa propre émotion. Notre rôle est de révéler cette identité et de la traduire avec justesse dans chaque détail, d’un <strong>site internet haut de gamme</strong> à une expérience <strong>e-commerce de luxe</strong>.'),
               BTNS([('Nos services UX et UI', L('agence-ux-cx-design-luxe/'))])))
    b.append(G('lfd-feat lfd-alt',
               IMG(UP + '2026/08/chanel-carre.png', 'Flacon Bleu de Chanel', 'Chanel · Référence parfum', href=L('chanel-experience-digitale/')),
               H(2, 'De la stratégie de marque à la création de site internet <em>de luxe</em>'),
               P('De la <strong>stratégie de marque</strong> à la <strong>création de site web</strong>, de l’<strong>identité visuelle</strong> au <strong>design d’interface (UI)</strong>, nous construisons des expériences qui ont du sens, de la personnalité et une véritable cohérence.'),
               P('La Framboise Digitale, c’est une approche où la marque, l’émotion, le design et la technologie avancent dans une même direction. Vous avez un projet de <strong>site internet sur mesure</strong> ? <strong>Contactez notre agence UX/UI à Paris ou Cannes.</strong>'),
               BTNS([('Contactez notre agence', L('contact/'))])))
    b.append(G('lfd-manifesto',
               H(2, '<strong>L’émotion commence</strong> <strong>bien avant le premier clic.</strong>'.replace('<strong>', '').replace('</strong>', '')),
               P('Chez La Framboise Digitale, nous pensons qu’une expérience forte ne se mesure pas seulement à ce qu’elle permet de faire, mais à ce qu’elle fait ressentir. Notre ambition : transformer le digital en une véritable expérience de marque.')))
    b.append(G('lfd-stack',
               panel(1, '2026/08/ChatGPT-Image-15-aout-2026-18_48_32.png', 'Interface Cartier, montre Panthère', 'Cartier : interface digitale pour le lancement d’une collection horlogère', 'Pour accompagner le lancement d’une nouvelle collection de montres, nous avons imaginé une expérience digitale à la hauteur de l’univers de la maison. L’enjeu était de concevoir une interface capable de mettre en scène le produit tout en préservant l’équilibre entre sophistication, désirabilité et simplicité d’usage.', 'Découvrir l’expérience digitale Cartier', 'cartier-experience-digitale/'),
               panel(2, '2026/11/dior-1-1-1-1.png', 'Interface Dior J’adore', 'Dior J’adore : UX design et expérience digitale d’un parfum iconique', 'Conception d’une expérience digitale dédiée à l’univers de J’adore.<br>De la réflexion UX au design des interfaces, chaque détail sublime la découverte du parfum.<br>Un parcours immersif, élégant et émotionnel, pensé comme une extension de son univers.', 'Découvrir l’expérience digitale Dior', 'dior-jadore-experience-digitale/'),
               panel(3, '2026/09/vignette.png', 'Visuel Le Monde', 'Le Monde : refonte de l’espace abonné et UX design éditorial', 'Refonte de l’espace abonné et conception d’une nouvelle expérience digitale.<br>Des parcours aux interfaces, nous avons repensé les contenus, les formats et les interactions.<br>Un design plus vivant, plus éditorial et pensé pour valoriser chaque contenu.', 'Découvrir l’expérience digitale Le Monde', 'le-monde-refonte-espace-abonne-paris/'),
               panel(4, '2026/09/lipton.png', 'Interface Lipton', 'Lipton : expérience digitale émotionnelle et ateliers de conception', 'Création d’une nouvelle proposition digitale autour d’une expérience plus émotionnelle et engageante.<br>Des ateliers de conception aux nouvelles maquettes, nous avons repensé les parcours, les interactions et la mise en scène de la marque.<br>Un design vivant et expressif, où l’émotion devient un véritable moteur de l’expérience.', 'Découvrir l’expérience digitale Lipton', 'lipton-experience-digitale/')))
    b.append(G('lfd-statement', H(3, 'Penser la marque. <em>Ressentir l’expérience.</em> Créer la différence.')))
    b.append(SC('[lfd_projets titre="Nos projets"]'))
    b.append(G('lfd-feat lfd-alt',
               IMG(UP + '2026/09/celine.png', 'Interface Celine', 'Celine · Référence mode', href=L('celine-experience-digitale-ux-ui/')),
               H(2, 'Agence de design digital à Paris : penser la marque, <em>créer la différence.</em>'),
               BTNS([('En savoir plus sur l’agence', L('agence-design-digital-luxe/'))])))
    b.append(G('lfd-exp',
               H(2, 'Nos expertises : branding, UX, direction artistique et technologie', 'lfd-eyebrow'),
               H(3, 'Le digital ne transforme rien. <em>Ce que vous en faites, oui.</em>'),
               P('Nous accompagnons les marques de la stratégie à la mise en ligne : comprendre, imaginer, concevoir et faire évoluer. <strong>Stratégie, design</strong> et <strong>technologie Ai</strong> au service d’idées capables de créer une véritable différence.'),
               BTNS([('Imaginer la suite', L('agence-design-digital-luxe/'))]),
               xrow('Branding', 'Branding de luxe et identité de marque', 'agence-branding-luxe/'),
               xrow('UX UI &amp; CX', 'UX UI &amp; CX design de luxe : parcours clients haut de gamme', 'agence-ux-cx-design-luxe/'),
               xrow('Design et émotion', 'Direction artistique digitale et design d’émotion', 'direction-artistique-digitale-luxe/'),
               xrow('Technologie et IA', 'Développement web, e-commerce et IA sur mesure', 'developpement-web-mobile-ia-luxe-paris/')))
    return '\n\n'.join(b)


SKIP = {'sample-page', 'specs', 'info', 'page-projets', 'expertises'}
out, report = {}, []
order = 0
for slug, p in D.items():
    entry = {'id': p['id'], 'type': 'page'}
    if slug == '':
        entry.update(type='accueil', content=build_home())
    elif slug in SKIP:
        report.append((slug, 'non importée (page vide ou de test)'))
        continue
    elif slug in PSLUG:
        _, client, cat, thumb = PSLUG[slug]
        entry.update(type='projet', client=client, cat=cat, thumb=UP + thumb, order=[x[0] for x in PROJ].index(slug) + 1, content=build_project(slug, p))
    elif slug == 'portfolio-agence-digitale-luxe':
        # mosaique dans l'ordre de la page actuelle (18 tuiles, dont Franck Provost et Ott sans page dediee)
        MOS = ['dior-jadore-experience-digitale', 'chanel-experience-digitale', 'celine-experience-digitale-ux-ui', 'festival-de-cannes-experience-digitale',
               'cartier-experience-digitale', 'renault-captur-experience-digitale-ux', 'adidas-experience-digitale-ux-design', 'renault-5-experience-digitale-ux-design',
               ('Franck Provost', 'Coiffure', '2026/09/provot.png'), ('Ott', 'Vin', '2026/09/ott.png'),
               'lipton-experience-digitale', 'dans-la-brume-electrique-experience-digitale', 'les-rivieres-pourpres-experience-digitale-ux-ui',
               'le-parisien-experience-digitale-ux-ui', 'le-monde-refonte-espace-abonne-paris', 'mugler-experience-digitale-ux-ui',
               'feadship-experience-digitale-ux-ui', 'sofinco-experience-digitale-ux-ui']
        tiles = []
        for m in MOS:
            if isinstance(m, tuple):
                tiles.append(IMG(UP + m[2], 'Projet ' + m[0], m[0] + ' · ' + m[1]))
            else:
                _, client, cat, thumb = PSLUG[m]
                tiles.append(IMG(UP + thumb, 'Projet ' + client, client + ' · ' + cat, href=L(m + '/')))
        entry.update(type='portfolio', content=G('lfd-hero', P('Portfolio', 'lfd-eyebrow'), H(1, 'Nos <em>projets</em>')) + '\n\n' + G('lfd-mosaic', *tiles))
    else:
        c = build_generic(slug, p)
        if not c:
            report.append((slug, 'vide'))
            continue
        if slug == 'developpement-web-mobile-ia-luxe-paris':
            c += '\n\n' + G('lfd-text', H(2, 'Des solutions adaptées à chaque projet'), P('Nous développons sur les plateformes qui conviennent à votre projet : WordPress, PrestaShop, Magento. Spécialistes de l’intégration, nous concevons aussi des outils sur mesure lorsque les solutions du marché ne suffisent pas.'))
            c += '\n\n' + G('lfd-logos', P('WordPress'), P('PrestaShop'), P('Magento'), P('Outils sur mesure'))
        kind = 'formation' if slug.startswith('formation') else 'contact' if slug == 'contact' else 'legal' if (slug.startswith('politique') or slug.startswith('conditions')) else 'expertise'
        entry.update(type=kind, content=c)
    out[slug] = entry
    report.append((slug or '(accueil)', '%s · %d blocs · %d caractères' % (entry['type'], entry['content'].count('<!-- wp:'), len(entry['content']))))
json.dump({'base': B, 'pages': out}, open(OUT, 'w', encoding='utf-8'), ensure_ascii=False, indent=0)
for r in report:
    print('%-72s %s' % r)
print(len(out), 'pages')
