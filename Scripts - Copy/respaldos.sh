
#!/bin/bash

# ==========================================
# VARIABLES
# ==========================================

carpetaBackups="/var/backups"
crearArchivo="sudo tar"
eliminarArchivo="sudo rm"


# ==========================================
# FUNCIONES
# ==========================================

prepararCarpetas() {
    sudo mkdir -p "$carpetaBackups/aplicaciones"
    sudo mkdir -p "$carpetaBackups/bases_datos"
}


listarBackups() {
    prepararCarpetas

    echo "========== RESPALDOS DE APLICACIONES =========="
    sudo ls -lh --time-style=long-iso "$carpetaBackups/aplicaciones"

    echo
    echo "========== RESPALDOS DE BASES DE DATOS =========="
    sudo ls -lh --time-style=long-iso "$carpetaBackups/bases_datos"
}


crearBackup() {
    echo "========== CREAR RESPALDO =========="
    echo "1 - Aplicacion"
    echo "2 - Base de datos"
    read -p "Elija el tipo: " tipo

    case "$tipo" in
        1)
            destino="$carpetaBackups/aplicaciones"
            prefijo="app"
            ;;
        2)
            destino="$carpetaBackups/bases_datos"
            prefijo="bd"
            ;;
        *)
            echo "Opcion no valida."
            return
            ;;
    esac

    read -p "Ruta de la carpeta o archivo a respaldar: " origen

    if [[ ! -e "$origen" ]]; then
        echo "La ruta indicada no existe."
        return
    fi

    prepararCarpetas

    fecha=$(date +"%Y-%m-%d_%H-%M-%S")
    archivo="$destino/${prefijo}_${fecha}.tar.gz"

    $crearArchivo -czf "$archivo" "$origen"

    if [ $? -eq 0 ]; then
        echo "Respaldo creado correctamente:"
        echo "$archivo"
    else
        echo "No se pudo crear el respaldo."
    fi
}


eliminarBackup() {
    listarBackups

    read -p "Ruta completa del respaldo a eliminar: " archivo

    case "$archivo" in
        "$carpetaBackups/aplicaciones/"*|"$carpetaBackups/bases_datos/"*)
            if [[ ! -f "$archivo" ]]; then
                echo "El archivo no existe."
                return
            fi
            ;;
        *)
            echo "Solo se pueden eliminar archivos dentro de las carpetas de respaldos."
            return
            ;;
    esac

    read -p "¿Confirma eliminar este respaldo? (si/no): " confirmar

    if [[ "$confirmar" == "si" ]]; then
        $eliminarArchivo -- "$archivo"
        echo "Operacion finalizada."
    else
        echo "Operacion cancelada."
    fi
}


verEspacio() {
    echo "========== ESPACIO DISPONIBLE =========="
    df -h "$carpetaBackups"
}


# ==========================================
# MENU PRINCIPAL
# ==========================================

inicio="si"

while [[ "$inicio" == "si" ]]; do
    echo
    echo "========== GESTION DE RESPALDOS =========="
    echo "1 - Ver respaldos y fechas"
    echo "2 - Crear respaldo"
    echo "3 - Eliminar respaldo"
    echo "4 - Ver espacio disponible"
    echo "5 - Salir"

    read -p "Elija una opcion: " opcion

    case "$opcion" in
        1) listarBackups ;;
        2) crearBackup ;;
        3) eliminarBackup ;;
        4) verEspacio ;;
        5) inicio="no" ;;
        *) echo "Opcion no valida." ;;
    esac
done
