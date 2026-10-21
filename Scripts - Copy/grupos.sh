
#!/bin/bash

# ==========================================
# VARIABLES
# ==========================================

crearGrupo="sudo groupadd"
eliminarGrupo="sudo groupdel"
modificarGrupo="sudo groupmod"


# ==========================================
# FUNCIONES
# ==========================================

crearGrup() {
    read -p "Nombre del grupo: " nombreGrupo

    if getent group "$nombreGrupo" >/dev/null; then
        echo "El grupo ya existe."
        return
    fi

    $crearGrupo "$nombreGrupo"

    if [ $? -eq 0 ]; then
        echo "Grupo creado correctamente."
    fi
}


eliminarGrup() {
    read -p "Nombre del grupo a eliminar: " nombreGrupo

    if ! getent group "$nombreGrupo" >/dev/null; then
        echo "El grupo no existe."
        return
    fi

    read -p "¿Confirma eliminar el grupo? (si/no): " confirmar

    if [[ "$confirmar" == "si" ]]; then
        $eliminarGrupo "$nombreGrupo"
    else
        echo "Operacion cancelada."
    fi
}


modificarGrup() {
    read -p "Nombre actual del grupo: " nombreGrupo

    if ! getent group "$nombreGrupo" >/dev/null; then
        echo "El grupo no existe."
        return
    fi

    read -p "Nuevo nombre del grupo: " nuevoNombre

    if getent group "$nuevoNombre" >/dev/null; then
        echo "Ya existe un grupo con ese nombre."
        return
    fi

    $modificarGrupo -n "$nuevoNombre" "$nombreGrupo"
}


verGrupos() {
    echo "========== GRUPOS =========="
    cut -d: -f1 /etc/group
}


# ==========================================
# MENU PRINCIPAL
# ==========================================

inicio="si"

while [[ "$inicio" == "si" ]]; do
    echo
    echo "===== GESTION DE GRUPOS ====="
    echo "1 - Crear grupo"
    echo "2 - Eliminar grupo"
    echo "3 - Modificar grupo"
    echo "4 - Ver grupos"
    echo "5 - Salir"

    read -p "Elija una opcion: " opcion

    case "$opcion" in
        1) crearGrup ;;
        2) eliminarGrup ;;
        3) modificarGrup ;;
        4) verGrupos ;;
        5) inicio="no" ;;
        *) echo "Opcion no valida." ;;
    esac
done
