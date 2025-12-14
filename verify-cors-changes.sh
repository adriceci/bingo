#!/bin/bash

# Script de Verificación - CORS/HMR en Bingo
# Este script verifica que todos los cambios se aplicaron correctamente

echo "========================================"
echo "🔍 Verificación de Cambios CORS/HMR"
echo "========================================"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

ERRORS=0

# 1. Verificar vite.config.js
echo "1️⃣ Verificando vite.config.js..."
if grep -q "cors:" vite.config.js && grep -q "origin: '\*'" vite.config.js; then
    echo -e "${GREEN}✓ CORS configurado correctamente${NC}"
else
    echo -e "${RED}✗ CORS no encontrado${NC}"
    ERRORS=$((ERRORS + 1))
fi

if grep -q "process.env.VITE_HMR_HOST" vite.config.js; then
    echo -e "${GREEN}✓ HMR dinámico configurado${NC}"
else
    echo -e "${RED}✗ HMR dinámico no encontrado${NC}"
    ERRORS=$((ERRORS + 1))
fi

if grep -q "protocol.*process.env.VITE_HMR_PROTOCOL" vite.config.js; then
    echo -e "${GREEN}✓ Protocolo HMR variable${NC}"
else
    echo -e "${RED}✗ Protocolo HMR no es variable${NC}"
    ERRORS=$((ERRORS + 1))
fi

echo ""

# 2. Verificar .env.example
echo "2️⃣ Verificando .env.example..."
if grep -q "VITE_HMR_HOST" .env.example; then
    echo -e "${GREEN}✓ VITE_HMR_HOST en .env.example${NC}"
else
    echo -e "${RED}✗ VITE_HMR_HOST no en .env.example${NC}"
    ERRORS=$((ERRORS + 1))
fi

if grep -q "VITE_HMR_PORT" .env.example; then
    echo -e "${GREEN}✓ VITE_HMR_PORT en .env.example${NC}"
else
    echo -e "${RED}✗ VITE_HMR_PORT no en .env.example${NC}"
    ERRORS=$((ERRORS + 1))
fi

if grep -q "VITE_HMR_PROTOCOL" .env.example; then
    echo -e "${GREEN}✓ VITE_HMR_PROTOCOL en .env.example${NC}"
else
    echo -e "${RED}✗ VITE_HMR_PROTOCOL no en .env.example${NC}"
    ERRORS=$((ERRORS + 1))
fi

echo ""

# 3. Verificar docker-setup.sh
echo "3️⃣ Verificando docker-setup.sh..."
if grep -q "docker compose exec -T app npm install" docker-setup.sh; then
    echo -e "${GREEN}✓ npm install comando correcto${NC}"
else
    echo -e "${YELLOW}⚠ Verificar comando npm install${NC}"
fi

if grep -q "5173" docker-setup.sh | grep -v "5193"; then
    echo -e "${GREEN}✓ Puerto correcto (5173)${NC}"
else
    echo -e "${YELLOW}⚠ Verificar puerto en docker-setup.sh${NC}"
fi

echo ""

# 4. Verificar archivos de documentación
echo "4️⃣ Verificando archivos de documentación..."
DOCS=("VITE_CORS_SOLUTION.md" "VITE_CORS_TROUBLESHOOTING.md" "COMPARISON_BINGO_vs_HOMELAB.md" "IMPLEMENTATION_CHECKLIST.md" "CHANGES_SUMMARY.md")

for doc in "${DOCS[@]}"; do
    if [ -f "$doc" ]; then
        echo -e "${GREEN}✓ $doc existe${NC}"
    else
        echo -e "${RED}✗ $doc no encontrado${NC}"
        ERRORS=$((ERRORS + 1))
    fi
done

echo ""

# 5. Verificar .env si existe
echo "5️⃣ Verificando .env (si existe)..."
if [ -f .env ]; then
    if grep -q "VITE_HMR_HOST" .env; then
        echo -e "${GREEN}✓ VITE_HMR_HOST en .env${NC}"
    else
        echo -e "${YELLOW}⚠ VITE_HMR_HOST no en .env (ejecutar docker-setup.sh)${NC}"
    fi
else
    echo -e "${YELLOW}⚠ .env no existe (se creará con docker-setup.sh)${NC}"
fi

echo ""
echo "========================================"

if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ Todas las verificaciones pasaron!${NC}"
    echo ""
    echo "📋 Próximos pasos:"
    echo "1. Ejecuta: ./docker-setup.sh"
    echo "2. O manualmente:"
    echo "   - cp .env.example .env"
    echo "   - docker-compose down"
    echo "   - docker-compose up -d --build"
    echo ""
else
    echo -e "${RED}❌ Se encontraron $ERRORS error(es)${NC}"
    echo "Por favor, revisa los errores arriba"
fi

echo "========================================"
echo ""
