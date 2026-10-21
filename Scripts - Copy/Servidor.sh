#!/bin/bash 

ROL=$1

if [ -z "$ROL" ]; then
    echo "Uso: $0 {app|db|respaldos}"
    exit 1
fi

case "$ROL" in

    app)
        echo "Configurando servidor de aplicaciones"
        ./usuarios.sh
        ./grupos.sh
        ./red.sh
        ./firewall.sh
        ./logs.sh
        ./respaldos.sh
        ;;

    db)
        echo "Configurando servidor de base de datos"
        ./usuarios.sh
        ./grupos.sh
        ./red.sh
        ./firewall.sh
        ./logs.sh
        ./base_datos.sh
        ./respaldos.sh
        ;;

    respaldos)
        echo "Configurando servidor de Respaldos"
        ./usuarios.sh
        ./grupos.sh
        ./red.sh
        ./firewall.sh
        ./logs.sh
        ./respaldos.sh
        ;;

    *)
        echo "Rol no válido."
        echo "Usar: app | db | respaldos"
        exit 1
        ;;
esac