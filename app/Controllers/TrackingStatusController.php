<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class TrackingStatusController extends BaseController
{
    public function all()
    {
        $this->settings["title"] = "Estatus de Seguimiento (Pipeline)";
        $this->settings["url"] = 'auth/trackingstatus/all';

        $this->modalForm('/app/trackingstatus/create', 'Registrar Estatus', 'addtrackingstatus_modalform', [
            [
                'label' => 'Emoji',
                'placeholder' => 'Ej: 🏠',
                'type' => 'text',
                'name' => 'emoji',
                'required' => false,
            ],
            [
                'label' => 'Nombre del estatus',
                'placeholder' => 'Ej: En negociación',
                'type' => 'text',
                'name' => 'name',
                'required' => true,
            ],
            [
                'label' => 'Para el clasificador',
                'placeholder' => 'Qué dijo el cliente para caer en este estatus',
                'type' => 'text',
                'name' => 'classifier_hint',
                'required' => false,
            ],
        ]);

        $this->body['statuses'] = $this->TrackingStatus
            ->select('id, emoji, name, classifier_hint, is_active')
            ->orderBy('id', 'ASC')
            ->findAll();

        $this->generate_template($this->settings["url"]);
    }

    public function create()
    {
        $emoji = trim((string) $this->request->getPost('emoji'));
        $data = [
            'name' => $this->request->getPost('name'),
            'emoji' => $emoji !== '' ? $emoji : '🏷️',
            'classifier_hint' => $this->request->getPost('classifier_hint'),
            'is_active' => 1,
        ];

        $this->TrackingStatus->save($data);
        $insertId = $this->TrackingStatus->getInsertID();

        if ($this->TrackingStatus->db->affectedRows() > 0 && $insertId) {
            $this->session->setFlashdata(['success' => '¡Estatus registrado correctamente!']);
        } else {
            $this->session->setFlashdata(['error' => 'No hemos podido registrar el estatus.']);
        }

        return redirect()->to(base_url('/app/trackingstatus/all'));
    }

    public function edit($id)
    {
        $this->settings["title"] = "Editar Estatus #".$id;
        $this->settings["url"] = 'shared/form/form_edit';

        if (!$this->is_method_get()) {
            $emoji = trim((string) $this->request->getPost('emoji'));
            $data = [
                'name' => $this->request->getPost('name'),
                'emoji' => $emoji !== '' ? $emoji : '🏷️',
                'classifier_hint' => $this->request->getPost('classifier_hint'),
            ];

            $this->TrackingStatus->update($id, $data);

            if ($this->TrackingStatus->db->affectedRows() > 0) {
                $this->session->setFlashdata(['success' => '¡Editado correctamente!']);
            } else {
                $this->session->setFlashdata(['error' => 'No hemos podido guardar tus cambios.']);
            }

            return redirect()->to(base_url('/app/trackingstatus/edit/'.$id));
        }

        $this->body["trackingstatus_data"] = $this->TrackingStatus->where('id', $id)->first();

        if (empty($this->body["trackingstatus_data"])) {
            return redirect()->to(base_url('/app/trackingstatus/all'));
        }

        $this->settings["breadcrumb"] = [
            'previous_page_name' => 'Estatus de Seguimiento',
            'previous_page_url' => '/app/trackingstatus/all',
        ];

        $model_form = $this->body["trackingstatus_data"];
        $controls = [
            'is_controls' => true,
            'url_previous_page' => '/app/trackingstatus/all',
        ];
        $data = [
            [
                'label' => 'Emoji',
                'placeholder' => 'Ej: 🏠',
                'type' => 'text',
                'name' => 'emoji',
            ],
            [
                'label' => 'Nombre del estatus',
                'placeholder' => 'Ej: En negociación',
                'type' => 'text',
                'name' => 'name',
            ],
            [
                'label' => 'Para el clasificador',
                'placeholder' => 'Qué dijo el cliente para caer en este estatus',
                'type' => 'text',
                'name' => 'classifier_hint',
            ],
        ];

        $this->generate_form('/app/trackingstatus/edit/'.$id, 'Editar Estatus', 'edittrackingstatus_form', $data, $model_form, $controls);
        $this->generate_template($this->settings["url"]);
    }

    public function toggle($id)
    {
        $row = $this->TrackingStatus->where('id', $id)->first();
        if (empty($row)) {
            $this->session->setFlashdata(['error' => 'No encontramos ese estatus.']);

            return redirect()->to(base_url('/app/trackingstatus/all'));
        }

        $next = (int) ($row['is_active'] ?? 1) === 1 ? 0 : 1;
        $this->TrackingStatus->update($id, ['is_active' => $next]);
        $this->session->setFlashdata([
            'success' => $next === 1 ? 'Estatus activado.' : 'Estatus desactivado. Jev ya no lo usa.',
        ]);

        return redirect()->to(base_url('/app/trackingstatus/all'));
    }

    public function delete($id)
    {
        if ($id == 1) {
            $this->session->setFlashdata(['error' => 'No se puede eliminar el estatus por defecto.']);

            return redirect()->to(base_url('/app/trackingstatus/all'));
        }

        $this->TrackingStatus->delete($id);

        if ($this->TrackingStatus->db->affectedRows() > 0) {
            $this->session->setFlashdata(['success' => '¡Eliminado correctamente!']);
        } else {
            $this->session->setFlashdata(['error' => 'No hemos podido eliminar el estatus.']);
        }

        return redirect()->to(base_url('/app/trackingstatus/all'));
    }
}
