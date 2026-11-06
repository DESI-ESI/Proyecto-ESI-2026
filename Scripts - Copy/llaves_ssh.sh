
#!/bin/bash

echo "=================================="
echo " CONFIGURACION DE LLAVES SSH"
echo "=================================="

read -p "Ingrese el nombre del trabajador: " USUARIO


if ! id "$USUARIO" &>/dev/null; then
    echo "El usuario no existe."
    exit 1
fi


HOME_USUARIO=$(getent passwd "$USUARIO" | cut -d: -f6)

if [ -z "$HOME_USUARIO" ]; then
    echo "No se pudo encontrar el directorio del usuario."
    exit 1
fi

mkdir -p "$HOME_USUARIO/.ssh"


echo "Pegue la clave publica SSH del trabajador:"
read -r CLAVE

if [[ ! "$CLAVE" =~ ^(ssh-ed25519|ssh-rsa|ecdsa-sha2-nistp[0-9]+)[[:space:]] ]]; then
    echo "La clave publica no tiene un formato valido."
    exit 1
fi


echo "$CLAVE" >> "$HOME_USUARIO/.ssh/authorized_keys"

chown -R "$USUARIO:$USUARIO" "$HOME_USUARIO/.ssh"
chmod 700 "$HOME_USUARIO/.ssh"
chmod 600 "$HOME_USUARIO/.ssh/authorized_keys"

echo "=================================="
echo "Clave SSH configurada correctamente"
echo "Trabajador: $USUARIO"
echo "=================================="
