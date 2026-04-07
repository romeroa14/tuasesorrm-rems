<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
        <div class="sidebar-brand-text mx-3">
            AGILIZADOR
        </div>
        <div class="sidebar-brand-icon">
            <img src="<?= base_url('img/logo/circle-logo.png') ?>">
        </div>
    </a>
    <li class="nav-item <?= $title == 'Panel' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= base_url('/app/dashboard') ?>">
        <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Panel</span>
        </a>
    </li>

    <?php if(session()->get('id_fk_rol') == 2): ?>
    <li class="nav-item <?= $title == 'Historial de acciones' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= base_url('/app/activity_log/all') ?>">
            <i class="fa fa-history"></i>
            <span>Historial de acciones</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if(session()->get('id_fk_rol') == 1 || session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 5 || session()->get('id_fk_rol') == 6 || session()->get('id_fk_rol') == 8): ?>
        <li class="nav-item <?= $title == 'Mis visitas' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('/app/my_visits/all') ?>">
                <i class="fa fa-calendar-alt menu-icon"></i>
                <span>Mis visitas</span>
            </a>
        </li>
        <li class="nav-item <?= $title == 'Mis búsquedas inmobiliarias' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('/app/my_real_estate_searches/all') ?>">
                <i class="fa fa-search menu-icon"></i>
                <span>Mis búsquedas</span>
            </a>
        </li>
        <li class="nav-item <?= $title == 'Kit de captación' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('/app/pickup_kit') ?>">
                <i class="fa fa-book menu-icon"></i>
                <span>Kit de captación</span>
            </a>
        </li>
    <?php endif; ?>
    <br>
    <div class="sidebar-heading">
        Inmuebles
    </div>
    <?php if(session()->get('id_fk_rol') == 1 || session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 5 || session()->get('id_fk_rol') == 6 || session()->get('id_fk_rol') == 8): ?>
        <li class="nav-item <?= $title == 'Visitas' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('/app/visits/all') ?>">
                <i class="fa fa-calendar-alt menu-icon"></i>
                <span>Visitas</span>
            </a>
        </li>
        <li class="nav-item <?= $title == 'Mis propiedades' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('/app/my_properties/all') ?>">
                <i class="fa fa-home menu-icon"></i>
                <span>Mis inmuebles</span>
            </a>
        </li>
        <li class="nav-item <?= $title == 'Búsquedas inmobiliarias' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('/app/real_estate_searches/all') ?>">
                <i class="fa fa-search menu-icon"></i>
                <span>Búsquedas inmobiliarias</span>
            </a>
        </li>
    <?php endif; ?>
    <li class="nav-item <?= $title == 'Catálogo inmobiliario' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= base_url('/app/properties/all') ?>">
            <i class="fa fa-th menu-icon"></i>
            <span>Catálogo inmobiliario</span>
        </a>
    </li>
    <hr class="sidebar-divider">
    <?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 8 || session()->get('id_fk_rol') == 6): ?>
        <div class="sidebar-heading">
            Captaciones
        </div>
        <li class="nav-item <?= $title == 'Declaraciones' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('/app/statements/all') ?>">
                <i class="fa fa-folder menu-icon"></i>
                <span>Declaraciones</span>
            </a>
        </li>
        <li class="nav-item <?= $title == 'Declaraciones desestimadas' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('/app/statements/dismissed/all') ?>">
                <i class="fa fa-minus-circle menu-icon"></i>
                <span>Captaciones desestimadas</span>
            </a>
        </li>
        <hr class="sidebar-divider">
    <?php endif; ?>
    

    
    <div class="sidebar-heading">
        Complementos
    </div>

    <?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 8): ?>
        <li class="nav-item">
        <a href="<?= base_url('/app/commission_sheets/all') ?>" class="nav-link">
            <i class="fa fa-file-invoice-dollar menu-icon"></i>
                <span>Fichas de comisiones</span>
            </a>
        </li>
    <?php endif; ?>

    <li class="nav-item">
        <a class="nav-link disabled">
            <i class="fa fa-cogs menu-icon"></i>
            <span>Generador AMC (Próximamente)</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link disabled">
            <i class="fa fa-cogs menu-icon"></i>
            <span>Propuesta de negocio (Próximamente)</span>
        </a>
    </li>


    <?php if(false): ?>
    <hr class="sidebar-divider">
        <div class="sidebar-heading">
            Clientes
        </div>
        <li class="nav-item <?= $title == 'ATC Leads' ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('/app/leads/atc/all') ?>">
                    <i class="fa fa-users menu-icon"></i>
                    <span>ATC leads</span>
                </a>
            </li>
    <?php endif; ?>



    <hr class="sidebar-divider">
    <?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 1 || session()->get('id_fk_rol') == 5 || session()->get('id_fk_rol') == 6 || session()->get('id_fk_rol') == 7 || session()->get('id_fk_rol') == 8): ?>
        <div class="sidebar-heading">
            Clientes
        </div>
        <?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 7 || session()->get('id_fk_rol') == 8): ?>
            <li class="nav-item <?= $title == 'ATC Leads' ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('/app/leads/all') ?>">
                    <i class="fa fa-users menu-icon"></i>
                    <span>ATC leads</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if(session()->get('id_fk_rol') == 2): ?>
            <li class="nav-item <?= $title == 'ATC Macro' ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('/app/macro_lead/all') ?>">
                    <i class="fa fa-users menu-icon"></i>
                    <span>ATC  macro</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if(session()->get('id_fk_rol') == 2): ?>
            <li class="nav-item <?= $title == 'Cruce ATC/Usuario' ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('/app/delegations/all') ?>">
                    <i class="fa fa-random menu-icon"></i>
                    <span>Cruce ATC/Usuario</span> 
                </a>
            </li>
        <?php endif; ?>
        <?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 5 || session()->get('id_fk_rol') == 6 || session()->get('id_fk_rol') == 8): ?>
            <li class="nav-item <?= $title == 'Leads delegados' ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('/app/delegates/all') ?>">
                    <i class="fa fa-users menu-icon"></i>
                    <span>Leads delegados</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 1 || session()->get('id_fk_rol') == 7 || session()->get('id_fk_rol') == 5 || session()->get('id_fk_rol') == 8): ?>
            <li class="nav-item <?= $title == 'Clientes asignados' ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('/app/assigned_clients/all') ?>">
                    <i class="fa fa-users menu-icon"></i>
                    <span>Clientes asignados</span>
                </a>
            </li>
        <?php endif; ?>
        <hr class="sidebar-divider">
    <?php endif; ?>
    <?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 4): ?>
        <div class="sidebar-heading">
            Mercadeo
        </div>
        <li class="nav-item <?= $title == 'Publicaciones RRSS' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('/app/marketing/publications/all') ?>">
                <i class="fa fa-table menu-icon"></i>
                <span>Publicaciones RRSS</span>
            </a>
        </li>
        <hr class="sidebar-divider">
    <?php endif; ?>
    <?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 8): ?>
        <div class="sidebar-heading">
            Servicios API
        </div>
        <li class="nav-item <?= $title == 'Wasi' ? 'active' : '' ?>">
            <a class="nav-link" href="<?= base_url('/app/services/wasi/all') ?>">
                <i class="fa fa-plug menu-icon"></i>
                <span>Wasi</span>
            </a>
        </li>
        <hr class="sidebar-divider">
    <?php endif; ?>
    <?php if(session()->get('id_fk_rol') == 2): ?>
        <div class="sidebar-heading">
            SUPER ACCIONES
        </div>
        <li class="nav-item">
            <a class="nav-link disabled" href="<?= base_url('') ?>">
                <i class="fa fa-user menu-icon"></i>
                <span>Usuarios (Próximamente)</span>
            </a>
        </li>
        <hr class="sidebar-divider">
    <?php endif; ?>
    <div class="version" id="version-ruangadmin"></div>
</ul>
<!-- Sidebar -->

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">