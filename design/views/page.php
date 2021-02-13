<?php
#declare(strict_types=1);

$ENV = ENV::go();

# Adapted from
# https://www.w3docs.com/learn-html/semantic-elements-in-html5.html
?>

<!doctype html>
<html>

<head>
    <title>
        <?= $ENV->SITE_NAME ?>
    </title>
</head>

<body>

    <header>
        <h1>
            <?= null # $HTML->logo()?>
        </h1>

        <nav>
            <?= null # $HTML->menu()?>
            <?= null # $HTML->search()?>
            <?= null # $HTML->account()?>
        </nav>
    </header>

    <main>
        <figure>
            <?= null # $HTML->toolbox()?>
        </figure>

        <section>
            <h2>
                <?= null # $Title?>
            </h2>

            <?= null # $Content?>
        </section>

        <section>
            <h2>
                <?= null # $Title?>
            </h2>

            <?= null # $Content?>
        </section>

        <aside>
            <h3>
                Sidebar
            </h3>

            <?= null # $HTML->info()?>
            <?= null # $HTML->stats()?>
        </aside>
    </main>

    <footer>
        <?= null # $HTML->footer()?>
        <address />
        <time />
    </footer>


</body>

</html>