<?php
//Sesión
require '../../includes/funciones.php';
$auth = estaAutenticado();

if (!$auth) {
    header('Location: /');
}

//BD
require '../../includes/config/database.php';
$db = conectarDB();

//Obtener el id de la url
$id = $_GET['id'];
$id = filter_var($id, FILTER_VALIDATE_INT);

//Si no existe el id
if (!$id) {
    header('Location: /admin');
}

//Obtener los datos de la propiedad
$consultaLlenar = "SELECT * FROM propiedades WHERE id = ${id}";
$resultadoLlenar = mysqli_query($db, $consultaLlenar);
$propiedad = mysqli_fetch_assoc($resultadoLlenar);

//Consultar para obtener los vendedores
$consulta = "SELECT * FROM vendedores";
$resultadoConsulta = mysqli_query($db, $consulta);

//Arreglo con mensajes de errores
$errores = [];

//Guardar valores de los campos
$titulo = $propiedad['titulo'];
$precio = $propiedad['precio'];
$imagenPropiedad = $propiedad['imagen'];
$descripcion = $propiedad['descripcion'];
$habitaciones = $propiedad['habitaciones'];
$wc = $propiedad['wc'];
$estacionamiento = $propiedad['estacionamiento'];
$id_vendedor = $propiedad['id_vendedor'];

/** Super Globales  **/
//Se ejecuta después de enviar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //mysqli_real_escape_string | Protege de que se suba scripting SQL a la BD (lo deshabilita y lo guarda como una entidad no ejecutable)
    $titulo = mysqli_real_escape_string($db, $_POST['titulo']);
    $precio = mysqli_real_escape_string($db, $_POST['precio']);
    $descripcion = mysqli_real_escape_string($db, $_POST['descripcion']);
    $habitaciones = mysqli_real_escape_string($db, $_POST['habitaciones']);
    $wc = mysqli_real_escape_string($db, $_POST['wc']);
    $estacionamiento = mysqli_real_escape_string($db, $_POST['estacionamiento']);
    $id_vendedor = mysqli_real_escape_string($db, $_POST['id_vendedor']);
    //Asignar files a una variable
    $imagen = $_FILES['imagen'];

    //Validación de campos
    if (!$titulo) {
        $errores[0] = "Añade un título a la propiedad";
    }

    //Ya no es obligatoria la imagen pero si la validación de su tamaño
    //Validar por tamaño (1Mb máximo)
    $medida = 1000 * 1000;
    if ($imagen['size'] > $medida) {
        $errores[] = '"La imagen es muy grande';
    }

    if (!$precio) {
        $errores[] = "Añade un precio a la propiedad";
    }

    if (strlen($descripcion) < 30) {
        $errores[] = "La descripción debe contener al menos 30 caracteres";
    }

    if (!$habitaciones) {
        $errores[] = "Añade el número de habitaciones de la propiedad";
    }

    if (!$wc) {
        $errores[] = "Añade el número de baños de la propiedad";
    }

    if (!$estacionamiento) {
        $errores[] = "Añade el número de lugares de estacionamieto de la propiedad";
    }

    if (!$id_vendedor) {
        $errores[] = "Elige un vendedor";
    }

    //Revisar que el arreglo de errores este vacio
    if (empty($errores)) {
        /** Subir archivos **/
        //Crear una carpeta
        $carpetaImagenes = '../../imagenes/';

        if (!is_dir($carpetaImagenes)) {
            mkdir($carpetaImagenes);
        }

        // $nombreImagen = '';

        //Validación para eliminar imagen previa
        if ($imagen['name']) {
            //Eliminar imagen previa
            unlink($carpetaImagenes . $imagenPropiedad);

            //Genera una nueva imagen
            //Generar un nombre único
            $nombreImagen = md5(uniqid(rand(), true)) . ".jpg";

            //Subir la imagen
            move_uploaded_file($imagen['tmp_name'], $carpetaImagenes . $nombreImagen);
        } else {
            //En caso de no subir nada
            $nombreImagen = $imagenPropiedad;
        }

        //Insertar en la BD
        $query = "UPDATE propiedades SET titulo = '$titulo', precio = $precio, imagen = '$nombreImagen', descripcion = '$descripcion', habitaciones = $habitaciones, wc = $wc, estacionamiento = $estacionamiento, id_vendedor = $id_vendedor WHERE  id = $id";

        $resultado = mysqli_query($db, $query);

        if ($resultado) {
            //Redireccionar al usuario
            //Funciona solo si esta antes del HTML
            header('Location: /admin?resultado=2');
        }
    }
}

incluirTemplate('header');
?>

<main class="contenedor seccion">
    <h1>Actualizar Propiedad</h1>

    <a href="/admin" class="boton-verde">Volver</a>

    <?php foreach ($errores as $error) : ?>
        <div class="alerta error">
            <?php echo $error; ?>
        </div>
    <?php endforeach; ?>

    <form class="formulario" method="POST" enctype="multipart/form-data">
        <fieldset>
            <legend>Información General</legend>

            <label for="titulo">Título:</label>
            <input type="text" id="titulo" name="titulo" placeholder="Título de Propiedad" value="<?php echo $titulo; ?>">

            <label for="precio">Precio:</label>
            <input type="number" id="precio" name="precio" placeholder="0.00" value="<?php echo $precio; ?>">

            <label for="imagen">Imagen:</label>
            <input type="file" id="imagen" name="imagen" accept="image/jpeg, image/png">
            <img src="/imagenes/<?php echo $imagenPropiedad ?>" alt="imagen propiedad <?php echo $id; ?>" class="imagen-small">

            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" placeholder="Descripción de Propiedad..."><?php echo $descripcion; ?></textarea>
        </fieldset>

        <fieldset>
            <legend>Información Propiedad</legend>

            <label for="habitaciones">Habitaciones:</label>
            <input type="number" id="habitaciones" name="habitaciones" placeholder="Ej. 3" value="<?php echo $habitaciones; ?>" min="1" max="9">

            <label for="wc">Baños:</label>
            <input type="number" id="wc" name="wc" placeholder="Ej. 3" value="<?php echo $wc; ?>" min="1" max="9">

            <label for="estacionamiento">Estacionamiento:</label>
            <input type="number" id="estacionamiento" name="estacionamiento" placeholder="Ej. 3" value="<?php echo $estacionamiento; ?>" min="1" max="9">
        </fieldset>

        <fieldset>
            <legend>Vendedor</legend>

            <select name="id_vendedor">
                <option <?php echo $id_vendedor === '' ? 'selected disabled' : 'disabled'; ?> value="">-- Seleccione --</option>
                <?php while ($vendedor = mysqli_fetch_assoc($resultadoConsulta)) : ?>
                    <option <?php echo $id_vendedor === $vendedor['id'] ? 'selected' : ''; ?> value="<?php echo $vendedor['id']; ?>"><?php echo $vendedor['nombre'] . " " . $vendedor['apellido']; ?></option>
                <?php endwhile; ?>
            </select>
        </fieldset>

        <input type="submit" value="Actualizar Propiedad" class="boton-verde">
    </form>
</main>

<?php incluirTemplate('footer'); ?>