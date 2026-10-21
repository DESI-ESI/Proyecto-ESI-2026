
#!/bin/bash

# ==========================================
# VARIABLES
# ==========================================

comandoLogs="sudo journalctl"


# ==========================================
# FUNCIONES
# ==========================================

verRecientes() {
    echo "========== ULTIMOS EVENTOS =========="
    $comandoLogs -n 30 --no-pager
}


verErrores() {
    echo "========== ERRORES DEL SISTEMA =========="
    $comandoLogs -p err -n 30 --no-pager
}


verArranque() {
    echo "========== EVENTOS DEL ULTIMO ARRANQUE =========="
    $comandoLogs -b -n 30 --no-pager
}


verSSH() {
    echo "========== REGISTROS DE SSH =========="
    $comandoLogs -u sshd -n 30 --no-pager
}


verServicio() {
    read -p "Nombre del servicio (ejemplo: httpd): " servicio

    if [[ -z "$servicio" ]]; then
        echo "Debe ingresar un servicio."
        return
    fi

    $comandoLogs -u "$servicio" -n 30 --no-pager
}


# ==========================================
# MENU PRINCIPAL
# ==========================================

inicio="si"

while [[ "$inicio" == "si" ]]; do
    echo
    echo "========== REGISTROS DEL SISTEMA =========="
    echo "1 - Ver eventos recientes"
    echo "2 - Ver errores"
    echo "3 - Ver eventos del arranque"
    echo "4 - Ver registros de SSH"
    echo "5 - Consultar un servicio"
    echo "6 - Salir"

    read -p "Elija una opcion: " opcion

    case "$opcion" in
        1) verRecientes ;;
        2) verErrores ;;
        3) verArranque ;;
        4) verSSH ;;
        5) verServicio ;;
        6) inicio="no" ;;
        *) echo "Opcion no valida." ;;
    esac
done
