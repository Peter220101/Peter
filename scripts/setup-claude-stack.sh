#!/usr/bin/env bash
# =============================================================================
# setup-claude-stack.sh — Bootstrap del stack de Claude Code para SERTAEJ
# =============================================================================
# Instala e integra, de forma idempotente y a nivel de USUARIO (global), de modo
# que quede disponible desde CUALQUIER consola/terminal:
#
#   1. superpowers@superpowers-marketplace   (obra/superpowers-marketplace)
#   2. codex@openai-codex                    (openai/codex-plugin-cc) + Codex CLI
#   3. gstack                                (garrytan/gstack, via clone + ./setup)
#
# Los marketplaces verticales (legal, financial, marketing, social) NO se
# instalan globalmente a proposito: se anaden por proyecto con --with-verticals.
#
# Uso:
#   ./scripts/setup-claude-stack.sh                # instalacion completa
#   ./scripts/setup-claude-stack.sh --doctor       # solo diagnostico
#   ./scripts/setup-claude-stack.sh --skip-gstack  # omitir gstack
#   ./scripts/setup-claude-stack.sh --with-verticals  # + marketplaces verticales (scope project)
#
# Re-ejecutable las veces que haga falta: detecta lo ya instalado y no duplica.
# =============================================================================

set -euo pipefail

# --- Configuracion -----------------------------------------------------------
CLAUDE_DIR="${CLAUDE_CONFIG_DIR:-$HOME/.claude}"
SKILLS_DIR="$CLAUDE_DIR/skills"
GSTACK_DIR="$SKILLS_DIR/gstack"
ENV_FILE="$CLAUDE_DIR/claude-stack-env.sh"
MARKER_BEGIN="# >>> claude-stack >>>"
MARKER_END="# <<< claude-stack <<<"

SKIP_GSTACK=0
SKIP_CODEX=0
SKIP_SUPERPOWERS=0
WITH_VERTICALS=0
DOCTOR_ONLY=0

# --- Salida ------------------------------------------------------------------
if [ -t 1 ]; then
  C_OK=$'\033[32m'; C_WARN=$'\033[33m'; C_ERR=$'\033[31m'; C_HEAD=$'\033[1;36m'; C_OFF=$'\033[0m'
else
  C_OK=''; C_WARN=''; C_ERR=''; C_HEAD=''; C_OFF=''
fi
head_() { printf '\n%s== %s ==%s\n' "$C_HEAD" "$*" "$C_OFF"; }
ok_()   { printf '%s  ok%s   %s\n' "$C_OK" "$C_OFF" "$*"; }
warn_() { printf '%s  warn%s %s\n' "$C_WARN" "$C_OFF" "$*"; }
err_()  { printf '%s  err%s  %s\n' "$C_ERR" "$C_OFF" "$*" >&2; }
info_() { printf '       %s\n' "$*"; }

usage() {
  sed -n '2,25p' "$0" | sed 's/^# \{0,1\}//'
  exit 0
}

while [ $# -gt 0 ]; do
  case "$1" in
    --skip-gstack)      SKIP_GSTACK=1 ;;
    --skip-codex)       SKIP_CODEX=1 ;;
    --skip-superpowers) SKIP_SUPERPOWERS=1 ;;
    --with-verticals)   WITH_VERTICALS=1 ;;
    --doctor)           DOCTOR_ONLY=1 ;;
    -h|--help)          usage ;;
    *) err_ "Opcion desconocida: $1"; exit 2 ;;
  esac
  shift
done

# --- Prerrequisitos ----------------------------------------------------------
check_prereqs() {
  head_ "Prerrequisitos"
  local fatal=0

  if command -v git >/dev/null 2>&1; then
    ok_ "git $(git --version | awk '{print $3}')"
  else
    err_ "git no encontrado (obligatorio)"; fatal=1
  fi

  if command -v claude >/dev/null 2>&1; then
    ok_ "claude $(claude --version 2>/dev/null | head -1)"
  else
    err_ "Claude Code CLI no encontrado. Instala: npm install -g @anthropic-ai/claude-code"
    fatal=1
  fi

  if command -v node >/dev/null 2>&1; then
    local nv major minor
    nv="$(node --version | sed 's/^v//')"
    major="${nv%%.*}"; minor="$(printf '%s' "$nv" | cut -d. -f2)"
    if [ "$major" -gt 18 ] 2>/dev/null || { [ "$major" -eq 18 ] && [ "$minor" -ge 18 ]; } 2>/dev/null; then
      ok_ "node v$nv"
    else
      warn_ "node v$nv es anterior a 18.18 — el plugin de Codex lo exige"
    fi
  else
    err_ "node no encontrado (obligatorio para Claude Code y Codex)"; fatal=1
  fi

  if command -v bun >/dev/null 2>&1; then
    ok_ "bun $(bun --version)"
  else
    warn_ "bun no encontrado — gstack lo necesita para compilar su binario browse"
    info_ "Instalar: curl -fsSL https://bun.sh/install | bash"
    [ "$SKIP_GSTACK" -eq 0 ] && { warn_ "gstack se omitira en esta pasada"; SKIP_GSTACK=1; }
  fi

  [ "$fatal" -eq 1 ] && { err_ "Faltan dependencias obligatorias. Abortando."; exit 1; }
  return 0
}

# --- Marketplaces y plugins --------------------------------------------------
marketplace_present() {
  claude plugin marketplace list 2>/dev/null | grep -qiE "(^|[^a-z0-9-])$1([^a-z0-9-]|$)"
}
plugin_present() {
  claude plugin list 2>/dev/null | grep -qF "$1"
}

add_marketplace() {
  local repo="$1" name="$2" scope="${3:-user}"
  if marketplace_present "$name"; then
    ok_ "marketplace $name ya configurado"
    return 0
  fi
  info_ "anadiendo marketplace $repo ..."
  if claude plugin marketplace add "$repo" --scope "$scope" >/dev/null 2>&1; then
    ok_ "marketplace $name anadido (scope: $scope)"
  else
    err_ "no se pudo anadir el marketplace $repo"
    info_ "Si el error es 'Invalid schema: plugins.NN.source', actualiza Claude Code y reintenta."
    return 1
  fi
}

install_plugin() {
  local id="$1"
  if plugin_present "$id"; then
    ok_ "plugin $id ya instalado"
    return 0
  fi
  info_ "instalando plugin $id ..."
  if claude plugin install "$id" >/dev/null 2>&1; then
    ok_ "plugin $id instalado"
  else
    err_ "fallo la instalacion de $id"
    info_ "Revisa los nombres reales con: claude plugin marketplace list"
    return 1
  fi
}

do_superpowers() {
  head_ "superpowers"
  add_marketplace "obra/superpowers-marketplace" "superpowers-marketplace" user || return 1
  install_plugin "superpowers@superpowers-marketplace"
}

do_codex() {
  head_ "codex-plugin-cc"
  # El slug del repo es openai/codex-plugin-cc pero el marketplace se llama openai-codex.
  add_marketplace "openai/codex-plugin-cc" "openai-codex" user || return 1
  install_plugin "codex@openai-codex" || return 1

  if command -v codex >/dev/null 2>&1; then
    ok_ "Codex CLI $(codex --version 2>/dev/null | head -1)"
  else
    info_ "instalando Codex CLI (npm -g @openai/codex) ..."
    if npm install -g @openai/codex >/dev/null 2>&1; then
      ok_ "Codex CLI $(codex --version 2>/dev/null | head -1)"
    else
      warn_ "no se pudo instalar @openai/codex; hazlo a mano: npm install -g @openai/codex"
    fi
  fi
  info_ "Dentro de Claude Code, ejecuta una vez: /codex:setup"
}

do_gstack() {
  head_ "gstack"
  mkdir -p "$SKILLS_DIR"
  if [ -d "$GSTACK_DIR/.git" ]; then
    info_ "gstack ya presente, actualizando ..."
    git -C "$GSTACK_DIR" fetch --depth 1 origin >/dev/null 2>&1 || warn_ "fetch fallido, se usa la copia local"
    git -C "$GSTACK_DIR" reset --hard origin/HEAD >/dev/null 2>&1 \
      || git -C "$GSTACK_DIR" pull --ff-only >/dev/null 2>&1 \
      || warn_ "no se pudo actualizar; se reutiliza la copia existente"
  else
    info_ "clonando gstack ..."
    git clone --single-branch --depth 1 https://github.com/garrytan/gstack.git "$GSTACK_DIR" >/dev/null 2>&1 \
      || { err_ "clone de gstack fallido"; return 1; }
  fi

  info_ "ejecutando ./setup (puede tardar varios minutos) ..."
  if ( cd "$GSTACK_DIR" && ./setup --no-prefix ); then
    ok_ "gstack instalado — skills registradas en $SKILLS_DIR"
  else
    warn_ "./setup termino con errores; revisa la salida de arriba"
  fi
}

do_verticals() {
  head_ "Marketplaces verticales (scope: project)"
  info_ "Se anaden SOLO a este repo. Elige plugins con /plugin dentro de Claude Code."
  add_marketplace "anthropics/financial-services"  "claude-for-financial-services" project || true
  add_marketplace "anthropics/claude-for-legal"    "claude-for-legal"              project || true
  add_marketplace "coreyhaines31/marketingskills"  "marketingskills"               project || true
  add_marketplace "social-media-skills/skills"     "social-media-skills"           project || true
}

# --- Cableado de shell: disponible desde CUALQUIER consola -------------------
detect_chromium() {
  # Respeta un override explicito del usuario.
  if [ -n "${GSTACK_CHROMIUM_PATH:-}" ] && [ -x "${GSTACK_CHROMIUM_PATH}" ]; then
    printf '%s' "$GSTACK_CHROMIUM_PATH"; return 0
  fi
  local c
  for c in \
    "${PLAYWRIGHT_BROWSERS_PATH:-/opt/pw-browsers}/chromium" \
    "$HOME"/.cache/ms-playwright/chromium-*/chrome-linux/chrome \
    "$HOME"/Library/Caches/ms-playwright/chromium-*/chrome-mac/Chromium.app/Contents/MacOS/Chromium
  do
    [ -x "$c" ] && { printf '%s' "$c"; return 0; }
  done
  return 1
}

write_env_file() {
  head_ "Archivo de entorno"
  mkdir -p "$CLAUDE_DIR"
  local chromium=""
  chromium="$(detect_chromium 2>/dev/null || true)"

  {
    printf '%s\n' '#!/bin/sh'
    printf '%s\n' '# Generado por scripts/setup-claude-stack.sh — no editar a mano.'
    printf '%s\n' '# Se carga desde .bashrc / .zshrc / .profile para que el stack de'
    printf '%s\n' '# Claude Code funcione desde CUALQUIER consola o terminal.'
    printf '%s\n' ''
    printf 'CLAUDE_STACK_GSTACK_DIR="%s"\n' "$GSTACK_DIR"
    printf '%s\n' 'export CLAUDE_STACK_GSTACK_DIR'
    printf '%s\n' ''
    printf '%s\n' '# gstack/bin en PATH (gstack-config, gstack-settings-hook, browse, ...)'
    printf '%s\n' 'if [ -d "$CLAUDE_STACK_GSTACK_DIR/bin" ]; then'
    printf '%s\n' '  case ":$PATH:" in'
    printf '%s\n' '    *":$CLAUDE_STACK_GSTACK_DIR/bin:"*) ;;'
    printf '%s\n' '    *) PATH="$CLAUDE_STACK_GSTACK_DIR/bin:$PATH" ;;'
    printf '%s\n' '  esac'
    printf '%s\n' '  export PATH'
    printf '%s\n' 'fi'
    printf '%s\n' ''
    printf '%s\n' '# npm global bin en PATH (claude, codex) si no estuviera ya'
    printf '%s\n' 'if command -v npm >/dev/null 2>&1; then'
    printf '%s\n' '  CLAUDE_STACK_NPM_BIN="$(npm prefix -g 2>/dev/null)/bin"'
    printf '%s\n' '  if [ -d "$CLAUDE_STACK_NPM_BIN" ]; then'
    printf '%s\n' '    case ":$PATH:" in'
    printf '%s\n' '      *":$CLAUDE_STACK_NPM_BIN:"*) ;;'
    printf '%s\n' '      *) PATH="$PATH:$CLAUDE_STACK_NPM_BIN" ;;'
    printf '%s\n' '    esac'
    printf '%s\n' '    export PATH'
    printf '%s\n' '  fi'
    printf '%s\n' '  unset CLAUDE_STACK_NPM_BIN'
    printf '%s\n' 'fi'
    printf '%s\n' ''
    if [ -n "$chromium" ]; then
      printf '%s\n' '# Chromium ya presente en el sistema: evita la descarga de Playwright.'
      printf '%s\n' 'if [ -z "${GSTACK_CHROMIUM_PATH:-}" ]; then'
      printf '  if [ -x "%s" ]; then\n' "$chromium"
      printf '    GSTACK_CHROMIUM_PATH="%s"\n' "$chromium"
      printf '%s\n' '    export GSTACK_CHROMIUM_PATH'
      printf '%s\n' '  fi'
      printf '%s\n' 'fi'
    else
      printf '%s\n' '# Sin Chromium preinstalado detectado; Playwright lo descargara al usarlo.'
    fi
  } > "$ENV_FILE"

  chmod 0644 "$ENV_FILE"
  ok_ "escrito $ENV_FILE"
  [ -n "$chromium" ] && ok_ "Chromium detectado: $chromium"
  return 0
}

wire_rc() {
  local rc="$1"
  [ -e "$rc" ] || : > "$rc"
  if grep -qF "$MARKER_BEGIN" "$rc" 2>/dev/null; then
    ok_ "$rc ya cableado"
    return 0
  fi
  {
    printf '\n%s\n' "$MARKER_BEGIN"
    printf '%s\n' "[ -f \"\$HOME/.claude/claude-stack-env.sh\" ] && . \"\$HOME/.claude/claude-stack-env.sh\""
    printf '%s\n' "$MARKER_END"
  } >> "$rc"
  ok_ "cableado $rc"
}

wire_fish() {
  local fish_cfg="$HOME/.config/fish/config.fish"
  command -v fish >/dev/null 2>&1 || return 0
  mkdir -p "$(dirname "$fish_cfg")"
  [ -e "$fish_cfg" ] || : > "$fish_cfg"
  if grep -qF "$MARKER_BEGIN" "$fish_cfg" 2>/dev/null; then
    ok_ "$fish_cfg ya cableado"
    return 0
  fi
  {
    printf '\n%s\n' "$MARKER_BEGIN"
    printf '%s\n' 'if test -d "$HOME/.claude/skills/gstack/bin"'
    printf '%s\n' '    fish_add_path "$HOME/.claude/skills/gstack/bin"'
    printf '%s\n' 'end'
    printf '%s\n' "$MARKER_END"
  } >> "$fish_cfg"
  ok_ "cableado $fish_cfg"
}

wire_shells() {
  head_ "Integracion con las shells"
  wire_rc "$HOME/.bashrc"
  wire_rc "$HOME/.zshrc"
  wire_rc "$HOME/.profile"
  # macOS: Terminal.app arranca bash/zsh como login shell.
  [ -f "$HOME/.bash_profile" ] && wire_rc "$HOME/.bash_profile"
  wire_fish
  info_ "Abre una terminal nueva (o: source ~/.claude/claude-stack-env.sh) para aplicarlo."
}

# --- Diagnostico -------------------------------------------------------------
doctor() {
  head_ "Diagnostico"
  command -v claude >/dev/null 2>&1 && ok_ "claude   $(claude --version 2>/dev/null | head -1)" || err_ "claude ausente"
  command -v node   >/dev/null 2>&1 && ok_ "node     $(node --version)"                        || err_ "node ausente"
  command -v bun    >/dev/null 2>&1 && ok_ "bun      $(bun --version)"                         || warn_ "bun ausente"
  command -v codex  >/dev/null 2>&1 && ok_ "codex    $(codex --version 2>/dev/null | head -1)" || warn_ "codex CLI ausente"

  printf '\n'
  info_ "Marketplaces:"
  claude plugin marketplace list 2>/dev/null | sed 's/^/       /' || warn_ "no se pudo listar"
  printf '\n'
  info_ "Plugins:"
  claude plugin list 2>/dev/null | sed 's/^/       /' || warn_ "no se pudo listar"
  printf '\n'

  if [ -d "$GSTACK_DIR" ]; then
    ok_ "gstack en $GSTACK_DIR (version $(cat "$GSTACK_DIR/VERSION" 2>/dev/null || echo '?'))"
    local n; n="$(find "$SKILLS_DIR" -maxdepth 1 -mindepth 1 -type d 2>/dev/null | wc -l | tr -d ' ')"
    ok_ "$n skills registradas en $SKILLS_DIR"
  else
    warn_ "gstack no instalado"
  fi

  [ -f "$ENV_FILE" ] && ok_ "env file $ENV_FILE" || warn_ "falta $ENV_FILE"

  # Comprueba el cableado en cada rc.
  local rc
  for rc in "$HOME/.bashrc" "$HOME/.zshrc" "$HOME/.profile"; do
    [ -f "$rc" ] || continue
    if grep -qF "$MARKER_BEGIN" "$rc" 2>/dev/null; then ok_ "cableado $rc"; else warn_ "sin cablear $rc"; fi
  done

  if command -v gstack-config >/dev/null 2>&1; then
    ok_ "gstack-config accesible desde el PATH actual"
  else
    warn_ "gstack-config no esta en el PATH de ESTA shell (abre una terminal nueva)"
  fi
}

# --- Main --------------------------------------------------------------------
main() {
  if [ "$DOCTOR_ONLY" -eq 1 ]; then
    doctor
    exit 0
  fi

  printf '%s\n' "Bootstrap del stack de Claude Code (usuario: $(whoami), home: $HOME)"
  check_prereqs

  [ "$SKIP_SUPERPOWERS" -eq 0 ] && { do_superpowers || warn_ "superpowers incompleto"; }
  [ "$SKIP_CODEX"       -eq 0 ] && { do_codex       || warn_ "codex incompleto"; }
  [ "$SKIP_GSTACK"      -eq 0 ] && { do_gstack      || warn_ "gstack incompleto"; }
  [ "$WITH_VERTICALS"   -eq 1 ] && do_verticals

  write_env_file
  wire_shells
  doctor

  head_ "Listo"
  info_ "1) Abre una terminal NUEVA (o: source ~/.claude/claude-stack-env.sh)"
  info_ "2) Lanza Claude Code y ejecuta: /reload-plugins"
  info_ "3) Solo la primera vez, para Codex: /codex:setup"
}

main "$@"
