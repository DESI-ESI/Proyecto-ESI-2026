
#!/bin/bash

SERVIDOR=$(hostname)

case "$SERVIDOR" in

    app)
        echo "MENU SERVIDOR DE APLICACION"
        OPCION=0

        while [ "$OPCION" -ne 9 ]; do
            echo ""
            echo "1. Gestionar usuarios"
            echo "2. Gestionar llaves SSH"
            echo "3. Gestionar grupos y permisos"
            echo "4. Configurar red"
            echo "5. Configurar firewall"
            echo "6. Consultar logs"
            echo "7. Gestionar respaldos"
            echo "9. Salir"
            read -p "Seleccione una opcion: " OPCION

            case "$OPCION" in
                1) ./usuarios.sh ;;
                2) ./llaves_ssh.sh ;;
                3) ./grupos.sh ;;
                4) ./red.sh ;;
                5) ./firewall.sh ;;
                6) ./logs.sh ;;
                7) ./respaldos.sh ;;
                9) echo "Saliendo..." ;;
                *) echo "Opcion no valida" ;;
            esac
        done
        ;;

    db)
        echo "MENU SERVIDOR DE BASE DE DATOS"
        OPCION=0

        while [ "$OPCION" -ne 9 ]; do
            echo ""
            echo "1. Gestionar usuarios"
            echo "2. Gestionar llaves SSH"
            echo "3. Gestionar grupos y permisos"
            echo "4. Configurar red"
            echo "5. Configurar firewall"
            echo "6. Consultar logs"
            echo "7. Configurar base de datos"
            echo "8. Gestionar respaldos"
            echo "9. Salir"
            read -p "Seleccione una opcion: " OPCION

            case "$OPCION" in
                1) ./usuarios.sh ;;
                2) ./llaves_ssh.sh ;;
                3) ./grupos.sh ;;
                4) ./red.sh ;;
                5) ./firewall.sh ;;
                6) ./logs.sh ;;
                7) ./base_datos.sh ;;
                8) ./respaldos.sh ;;
                9) echo "Saliendo..." ;;
                *) echo "Opcion no valida" ;;
            esac
        done
        ;;

    respaldos)
        echo "MENU SERVIDOR DE RESPALDOS"
        OPCION=0

        while [ "$OPCION" -ne 9 ]; do
            echo ""
            echo "1. Gestionar usuarios"
            echo "2. Gestionar llaves SSH"
            echo "3. Gestionar grupos y permisos"
            echo "4. Configurar red"
            echo "5. Configurar firewall"
            echo "6. Consultar logs"
            echo "7. Gestionar respaldos"
            echo "9. Salir"
            read -p "Seleccione una opcion: " OPCION

            case "$OPCION" in
                1) ./usuarios.sh ;;
                2) ./llaves_ssh.sh ;;
                3) ./grupos.sh ;;
                4) ./red.sh ;;
                5) ./firewall.sh ;;
                6) ./logs.sh ;;
                7) ./respaldos.sh ;;
                9) echo "Saliendo..." ;;
                *) echo "Opcion no valida" ;;
            esac
        done
        ;;

    *)
        echo "Error: servidor no reconocido."
        exit 1
        ;;

esac
