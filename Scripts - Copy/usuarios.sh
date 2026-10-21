#!/bin/bash


crearUsuario="sudo useradd"
eliminarUsuario="sudo userdel -r"



crearUser() {

    read -p "Escriba un nombre para el usuario: " nombre

    if id "$nombre" &>/dev/null; then
        echo "El usuario '$nombre' ya existe."
        return
    fi

    $crearUsuario -m "$nombre"

    read -sp "Escriba la contrasena: " contrasena
    echo

    echo "$nombre:$contrasena" | sudo chpasswd

    echo "Usuario creado correctamente."
}


eliminarUser() {

    read -p "Escriba el nombre del usuario a eliminar: " nombre

    if ! id "$nombre" &>/dev/null; then
        echo "El usuario '$nombre' no existe."
        return
    fi

    $eliminarUsuario "$nombre"

    echo "Usuario eliminado correctamente."
}


modificarUser() {

    read -p "Escriba el nombre del usuario: " nombre

    if ! id "$nombre" &>/dev/null; then
        echo "El usuario '$nombre' no existe."
        return
    fi

    echo
    echo "--------------------------------"
    echo "       MODIFICAR USUARIO"
    echo "--------------------------------"
    echo "1 - Cambiar nombre"
    echo "2 - Cambiar home"
    echo "3 - Agregar a un grupo"
    echo "4 - Cambiar contrasena"

    read -p "Elija una opcion: " opcion

    case $opcion in

        1)
            read -p "Escriba el nuevo nombre: " nuevoNombre

            if id "$nuevoNombre" &>/dev/null; then
                echo "El usuario '$nuevoNombre' ya existe."
            else
                sudo usermod -l "$nuevoNombre" "$nombre"
                echo "Nombre modificado correctamente."
            fi
            ;;

        2)
            read -p "Escriba el nuevo home: " home

            sudo usermod -d "/home/$home" -m "$nombre"

            echo "Home modificado correctamente."
            ;;

        3)
            read -p "Escriba el grupo: " grupo

            if getent group "$grupo" &>/dev/null; then
                sudo usermod -aG "$grupo" "$nombre"
                echo "Usuario agregado al grupo correctamente."
            else
                echo "El grupo '$grupo' no existe."
            fi
            ;;

        4)
            read -sp "Escriba la nueva contrasena: " contrasena
            echo

            echo "$nombre:$contrasena" | sudo chpasswd

            echo "Contrasena modificada correctamente."
            ;;

        *)
            echo "Opcion no valida."
            ;;

    esac
}


verUsuarios() {

    echo
    echo "--------------------------------"
    echo "            USUARIOS"
    echo "--------------------------------"

    cut -d: -f1 /etc/passwd
}

inicio="si"

while [[ $inicio == "si" ]]; do

    echo
    echo "================================"
    echo "       GESTION DE USUARIOS"
    echo "================================"
    echo
    echo "1 - Crear usuario"
    echo "2 - Eliminar usuario"
    echo "3 - Modificar usuario"
    echo "4 - Ver usuarios"
    echo "5 - Salir"
    echo

    read -p "Elija una opcion: " opcion

    case $opcion in

        1)
            crearUser
            ;;

        2)
            eliminarUser
            ;;

        3)
            modificarUser
            ;;

        4)
            verUsuarios
            ;;

        5)
            inicio="no"
            echo "Saliendo..."
            ;;

        *)
            echo "Opcion no valida."
            ;;

    esac

done