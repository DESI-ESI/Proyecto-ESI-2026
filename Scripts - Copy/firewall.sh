
#!/bin/bash

# ==========================================
# VARIABLES
# ==========================================

firewall="sudo firewall-cmd"
servicioFirewall="sudo systemctl"


# ==========================================
# FUNCIONES
# ==========================================

verEstado() {
    $servicioFirewall status firewalld --no-pager
}


verReglas() {
    echo "========== ZONA ACTIVA =========="
    $firewall --get-active-zones

    echo
    echo "========== SERVICIOS PERMITIDOS =========="
    $firewall --list-all
}


iniciarFirewall() {
    $servicioFirewall enable --now firewalld
    echo "Se solicito iniciar y habilitar el firewall."
}


agregarServicio() {
    read -p "Servicio a permitir (ssh, http, https): " servicio

    case "$servicio" in
        ssh|http|https)
            $firewall --permanent --add-service="$servicio"

            if [ $? -eq 0 ]; then
                $firewall --reload
                echo "Servicio agregado."
            fi
            ;;
        *)
            echo "Servicio no permitido por este menu."
            ;;
    esac
}


quitarServicio() {
    read -p "Servicio a quitar (ssh, http, https): " servicio

    case "$servicio" in
        ssh|http|https)
            read -p "¿Confirma quitar $servicio? (si/no): " confirmar

            if [[ "$confirmar" == "si" ]]; then
                $firewall --permanent --remove-service="$servicio"
                $firewall --reload
                echo "Operacion realizada."
            else
                echo "Operacion cancelada."
            fi
            ;;
        *)
            echo "Servicio no permitido por este menu."
            ;;
    esac
}


# ==========================================
# MENU PRINCIPAL
# ==========================================

inicio="si"

while [[ "$inicio" == "si" ]]; do
    echo
    echo "========== GESTION DEL FIREWALL =========="
    echo "1 - Ver estado"
    echo "2 - Ver reglas"
    echo "3 - Iniciar y habilitar firewall"
    echo "4 - Permitir servicio"
    echo "5 - Quitar servicio"
    echo "6 - Salir"

    read -p "Elija una opcion: " opcion

    case "$opcion" in
        1) verEstado ;;
        2) verReglas ;;
        3) iniciarFirewall ;;
        4) agregarServicio ;;
        5) quitarServicio ;;
        6) inicio="no" ;;
        *) echo "Opcion no valida." ;;
    esac
done
