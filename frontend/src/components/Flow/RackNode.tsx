import clsx from 'clsx';
import { Button } from 'flowbite-react';
import React, { memo } from 'react';

const RackNode = memo(({ data, id }: any) => {
  return (
    <>
      <div className="group">
        <div className={clsx("border h-40 w-20 flex items-center justify-center text-center text-ellipsis overflow-hidden", data.selected && 'border-white border-4', data.project != null || data.project != undefined ? 'bg-red-600' : 'bg-green-600')}>
          <div>
            <p>{data.label}</p>
            <p>{data.project?.name}</p>
          </div>
        </div>
        {!data.displayOnly && data.project === null &&
          <div className='group-hover:flex flex-wrap gap-2 pt-2 hidden'>
            <Button className='nodrag inline' onClick={() => data.edit({ id: id, data: data })}>Edit</Button>
            <Button className='inline nodrag' color="failure" onClick={() => data.delete({ id: id, data: data })}>Delete</Button>
          </div>
        }
      </div>
    </>
  );
});
RackNode.displayName = 'RackNode'

export default RackNode
