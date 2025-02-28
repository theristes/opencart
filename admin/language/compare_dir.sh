#!/bin/bash

# Script Etapa 1: Comparar estrutura de diretórios de localização (existência de ficheiros)

usage() {
  echo "Uso: $0 <diretorio_en_gb> <diretorio_pt_br>"
  echo "Compara a estrutura de diretórios de localização de en-gb com pt-br e reporta ficheiros/pastas ausentes em pt-br."
  echo "Exemplo: $0 upload/admin/language/en-gb upload/admin/language/pt-br"
  exit 1
}

if [ "$#" -ne 2 ]; then
  usage
fi

dir_en_gb="$1"
dir_pt_br="$2"

if [ ! -d "$dir_en_gb" ]; then
  echo "Erro: Diretório de idioma 'en-gb' ('$dir_en_gb') não existe ou não é um diretório."
  usage
fi

if [ ! -d "$dir_pt_br" ]; then
  echo "Erro: Diretório de idioma 'pt-br' ('$dir_pt_br') não existe ou não é um diretório."
  usage
fi

echo "--- Etapa 1: Comparando estrutura de diretórios de localização ---"
echo "--- Verificando se a estrutura de '$dir_en_gb' existe em '$dir_pt_br' ---"

# *** 1. Listar pastas ausentes em pt-br ***
echo ""
echo "--- Pastas ausentes em '$dir_pt_br' em comparação com '$dir_en_gb': ---"
find "$dir_en_gb" -type d | while IFS= read -r dir_en; do
  relative_dir=$(echo "$dir_en" | sed "s|^$dir_en_gb/||")
  if [ -n "$relative_dir" ]; then # Ignorar o próprio diretório base
    dir_pt="$dir_pt_br/$relative_dir"
    if ! [ -d "$dir_pt" ]; then
      echo "$relative_dir"
    fi
  fi
done

# *** 2. Listar ficheiros ausentes em pt-br ***
echo ""
echo "--- Ficheiros ausentes em '$dir_pt_br' em comparação com '$dir_en_gb': ---"
find "$dir_en_gb" -type f | while IFS= read -r file_en; do
  relative_file=$(echo "$file_en" | sed "s|^$dir_en_gb/||")
  file_pt="$dir_pt_br/$relative_file"
  if ! [ -f "$file_pt" ]; then
    echo "$relative_file"
  fi
done

echo ""
echo "--- Etapa 1: Verificação da estrutura de diretórios completa ---"
echo "--- (Verifique a lista acima para ficheiros e pastas ausentes em '$dir_pt_br') ---"