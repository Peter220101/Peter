# Claude Code — stack de desarrollo

Instalacion reproducible de los plugins/skills de Claude Code que usamos para
programar en este proyecto. Todo se instala a **nivel de usuario**, asi que
queda disponible desde **cualquier consola o terminal** de la maquina.

## Instalacion

```bash
./scripts/setup-claude-stack.sh
```

Despues:

1. Abre una terminal **nueva** (o `source ~/.claude/claude-stack-env.sh`).
2. Lanza Claude Code y ejecuta `/reload-plugins`.
3. Solo la primera vez, para Codex: `/codex:setup`.

El script es **idempotente**: se puede volver a ejecutar cuantas veces haga
falta: detecta lo ya instalado, actualiza gstack y no duplica nada.

## Que instala

| Componente | Origen | Que aporta |
|---|---|---|
| `superpowers@superpowers-marketplace` | `obra/superpowers-marketplace` | Suite general de skills de desarrollo |
| `codex@openai-codex` | `openai/codex-plugin-cc` | Puente con el Codex CLI de OpenAI |
| Codex CLI | `npm -g @openai/codex` | Binario que el plugin anterior necesita |
| `gstack` | `garrytan/gstack` | ~58 skills: `/qa`, `/review`, `/ship`, `/investigate`, `/spec`, `/health`, `/cso`, ... |

> **Nota sobre nombres.** El slug del repo y el nombre del marketplace no
> siempre coinciden. `openai/codex-plugin-cc` registra el marketplace como
> `openai-codex`, por eso el plugin se instala como `codex@openai-codex`.
> Ante la duda: `claude plugin marketplace list`.

> **gstack no usa marketplace.** El metodo oficial sigue siendo clonar en
> `~/.claude/skills/gstack` y ejecutar `./setup`, que es justo lo que hace el
> script.

## Por que funciona desde cualquier consola

El script genera `~/.claude/claude-stack-env.sh` y lo carga desde `.bashrc`,
`.zshrc`, `.profile`, `.bash_profile` y `config.fish` mediante un bloque
delimitado:

```
# >>> claude-stack >>>
[ -f "$HOME/.claude/claude-stack-env.sh" ] && . "$HOME/.claude/claude-stack-env.sh"
# <<< claude-stack <<<
```

Ese archivo de entorno:

- Anade `~/.claude/skills/gstack/bin` al `PATH` (`gstack-config`,
  `gstack-settings-hook`, `browse`, ...).
- Anade el bin global de npm al `PATH` si faltara (`claude`, `codex`).
- Exporta `GSTACK_CHROMIUM_PATH` si detecta un Chromium ya instalado, para
  evitar que Playwright se descargue otro.

Verificado en `bash` (login e interactiva), `sh` (login) y preparado para
`zsh` y `fish`.

## Prerrequisitos

- `git`
- `node` >= 18.18 (lo exige el plugin de Codex)
- Claude Code CLI (`npm install -g @anthropic-ai/claude-code`)
- `bun` — solo para gstack, que compila su binario `browse`:
  `curl -fsSL https://bun.sh/install | bash`

Si falta `bun`, el script avisa y continua sin gstack en lugar de fallar.

## Opciones

```bash
./scripts/setup-claude-stack.sh --doctor          # solo diagnostico, no instala
./scripts/setup-claude-stack.sh --skip-gstack     # omitir gstack
./scripts/setup-claude-stack.sh --skip-codex      # omitir codex
./scripts/setup-claude-stack.sh --with-verticals  # + marketplaces verticales
```

### Marketplaces verticales

`financial-services`, `claude-for-legal`, `marketingskills` y
`social-media-skills` **no** se instalan globalmente a proposito. Cada skill
inyecta nombre y descripcion en cada arranque de sesion, asi que instalarlos
todos cuesta contexto en cada sesion aunque no se usen.

`--with-verticals` los anade con `--scope project`, es decir solo al repo
actual. Los plugins concretos se eligen despues con `/plugin`, porque son
bundles con varios plugins dentro.

## Seguridad

Un `SKILL.md` es un prompt que el agente obedece con permisos de Bash. Estos
repos son de terceros: `obra/superpowers-marketplace`, `openai/codex-plugin-cc`
y `garrytan/gstack`. El script fija el origen de cada uno para que quede por
escrito que se instala y desde donde.

## Diagnostico

```bash
./scripts/setup-claude-stack.sh --doctor
```

Comprueba binarios, marketplaces, plugins, skills registradas y el cableado de
cada shell.

## Problemas conocidos

**`Invalid schema: plugins.NN.source` al anadir un marketplace.** Bug de
validacion en versiones antiguas de Claude Code (p.ej. v2.1.58). Actualiza
Claude Code antes de insistir.

**`Chromium bootstrap: chromium-install` al instalar gstack.** `playwright
install chromium` no pudo descargar (red, proxy o descarga bloqueada). Todo lo
demas queda instalado y funcional; solo las skills que manejan navegador
(`/qa`, `/browse`, `/scrape`, `/design-review`, `/diagram`, ...) quedan
inactivas. Si ya hay un Chromium en la maquina, el script lo detecta y exporta
`GSTACK_CHROMIUM_PATH`; si no, arregla la descarga y vuelve a ejecutar el
script.

**`gstack-config: command not found` justo despues de instalar.** El `PATH` de
la shell actual es anterior al cableado. Abre una terminal nueva o ejecuta
`source ~/.claude/claude-stack-env.sh`.

## Arranque automatico en sesiones remotas (opcional)

Los contenedores remotos de Claude Code arrancan vacios: `~/.claude` no
sobrevive entre sesiones, asi que hay que ejecutar el script una vez por
sesion. Se puede automatizar con un hook `SessionStart` en
`.claude/settings.json` que lance el instalador en segundo plano. No esta
incluido en el repo: un hook se ejecuta solo, sin confirmacion, en cada
arranque de sesion, asi que conviene que sea una decision explicita.
