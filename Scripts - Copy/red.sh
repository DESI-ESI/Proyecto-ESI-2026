
#!/bin/bash

# ==========================================
# VARIABLES
# ==========================================

comandoIP="ip"
comandoPing="ping"
comandoRuta="ip route"


# ==========================================
# FUNCIONES
# ==========================================

verInterfaces() {
    echo "========== INTERFACES DE RED =========="
    $comandoIP -br address
}


verRutas() {
    echo "========== RUTAS Y PUERTA DE ENLACE =========="
    $comandoRuta
}


verDNS() {
    echo "========== SERVIDORES DNS =========="
    cat /etc/resolv.conf
}


probarConexion() {
    read -p "Ingrese la IP o nombre del destino: " destino

    if [[ -z "$destino" ]]; then
        echo "Debe ingresar un destino."
        return
    fi

    $comandoPing -c 4 "$destino"
}


verConfiguracion() {
    echo "========== CONFIGURACION DE RED =========="
    $comandoIP address
    echo
    $comandoRuta
}


# ==========================================
# MENU PRINCIPAL
# ==========================================

inicio="si"

while [[ "$inicio" == "si" ]]; do
    echo
    echo "========== GESTION DE RED =========="
    echo "1 - Ver interfaces e IP"
    echo "2 - Ver rutas y puerta de enlace"
    echo "3 - Ver DNS"
    echo "4 - Probar conexion"
    echo "5 - Ver toda la configuracion"
    echo "6 - Salir"

    read -p "Elija una opcion: " opcion

    case "$opcion" in
        1) verInterfaces ;;
        2) verRutas ;;
        3) verDNS ;;
        4) probarConexion ;;
        5) verConfiguracion ;;
        6) inicio="no" ;;
        *) echo "Opcion no valida." ;;
    esac
done
