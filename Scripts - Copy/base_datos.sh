
#!/bin/bash

# ==========================================
# VARIABLES
# ==========================================

servicioBD="mariadb"
comandoServicio="sudo systemctl"
comandoMariaDB="sudo mariadb"
comandoVolcado="sudo mariadb-dump"
carpetaBackups="/var/backups/bases_datos"


# ==========================================
# FUNCIONES
# ==========================================

verEstado() {
    $comandoServicio status "$servicioBD" --no-pager
}


iniciarBD() {
    $comandoServicio enable --now "$servicioBD"

    if [ $? -eq 0 ]; then
        echo "MariaDB esta iniciado y habilitado."
    fi
}


detenerBD() {
    read -p "¿Confirma detener MariaDB? (si/no): " confirmar

    if [[ "$confirmar" == "si" ]]; then
        $comandoServicio stop "$servicioBD"
    else
        echo "Operacion cancelada."
    fi
}


reiniciarBD() {
    $comandoServicio restart "$servicioBD"

    if [ $? -eq 0 ]; then
        echo "MariaDB reiniciado correctamente."
    fi
}


verBases() {
    echo "========== BASES DE DATOS =========="
    $comandoMariaDB -e "SHOW DATABASES;"
}


crearRespaldoBD() {
    read -p "Nombre de la base de datos: " nombreBD

    if [[ -z "$nombreBD" ]]; then
        echo "Debe ingresar un nombre."
        return
    fi

    if ! $comandoMariaDB -N -e "SHOW DATABASES;" |
        grep -Fxq -- "$nombreBD"; then
        echo "La base de datos no existe o no se pudo consultar."
        return
    fi

    sudo mkdir -p "$carpetaBackups"

    fecha=$(date +"%Y-%m-%d_%H-%M-%S")
    archivo="$carpetaBackups/${nombreBD}_${fecha}.sql"

    $comandoVolcado "$nombreBD" > "$archivo"

    if [ $? -eq 0 ]; then
        echo "Respaldo SQL creado:"
        echo "$archivo"
    else
        sudo rm -f -- "$archivo"
        echo "No se pudo crear el respaldo."
    fi
}


# ==========================================
# MENU PRINCIPAL
# ==========================================

inicio="si"

while [[ "$inicio" == "si" ]]; do
    echo
    echo "========== ADMINISTRACION MARIADB =========="
    echo "1 - Ver estado del servicio"
    echo "2 - Iniciar y habilitar MariaDB"
    echo "3 - Detener MariaDB"
    echo "4 - Reiniciar MariaDB"
    echo "5 - Ver bases de datos"
    echo "6 - Crear respaldo SQL"
    echo "7 - Salir"

    read -p "Elija una opcion: " opcion

    case "$opcion" in
        1) verEstado ;;
        2) iniciarBD ;;
        3) detenerBD ;;
        4) reiniciarBD ;;
        5) verBases ;;
        6) crearRespaldoBD ;;
        7) inicio="no" ;;
        *) echo "Opcion no valida." ;;
    esac
done
