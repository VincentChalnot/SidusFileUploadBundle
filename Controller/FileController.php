<?php

namespace Sidus\FileUploadBundle\Controller;

use Sidus\FileUploadBundle\Action\FileDownloadAction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Expose a download link for uploaded resources
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FileController extends Controller
{
    /**
     * @param string     $type
     * @param string|int $identifier
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function downloadAction($type, $identifier)
    {
        $m = __METHOD__.' is deprecated, consider using the '.FileDownloadAction::class.' action/service instead';
        @trigger_error($m, E_USER_DEPRECATED);

        return $this->get(FileDownloadAction::class)->__invoke($type, $identifier);
    }
}
