from pathlib import Path
import re
p = Path('resources/js/Pages/Penduduk/Index.vue')
s = p.read_text(encoding='utf-8')
# Find script block ranges to ignore
script_ranges = []
for m in re.finditer(r'<script[\s\S]*?</script>', s, flags=re.I):
    script_ranges.append((m.start(), m.end()))
# Find comment ranges to ignore
comment_ranges = []
for m in re.finditer(r'<!--([\s\S]*?)-->', s):
    comment_ranges.append((m.start(), m.end()))

def in_ranges(pos, ranges):
    for a,b in ranges:
        if a <= pos < b:
            return True
    return False

pattern = re.compile(r'<(/?)\s*([a-zA-Z0-9_:\-]+)([^>]*)>', re.M)
void_tags = set(['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'])
stack = []
errors = []
for m in pattern.finditer(s):
    pos = m.start()
    if in_ranges(pos, script_ranges) or in_ranges(pos, comment_ranges):
        continue
    closing = m.group(1) == '/'
    tag = m.group(2)
    attrs = m.group(3) or ''
    self_close = attrs.strip().endswith('/')
    tag_lower = tag.lower()
    if closing:
        if not stack:
            errors.append((pos, f'Unexpected closing tag </{tag}>'))
        else:
            top = stack[-1]
            if top[0].lower() == tag_lower:
                stack.pop()
            else:
                errors.append((pos, f'Mismatched closing tag </{tag}> expected </{top[0]}>'))
    else:
        if self_close or tag_lower in void_tags:
            continue
        stack.append((tag, pos))

print('Remaining stack size:', len(stack))
if stack:
    for t,pos in stack:
        line = s[:pos].count('\n')+1
        print(f'Unclosed <{t}> at line {line}')
if errors:
    print('\nErrors:')
    for pos,msg in errors:
        line = s[:pos].count('\n')+1
        print(f'Line {line}: {msg}')
else:
    print('No immediate mismatched closing tags found')
